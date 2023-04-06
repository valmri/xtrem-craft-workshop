<?php

namespace Tests\MoneyProblem\Domain;

use MoneyProblem\Domain\Bank;
use MoneyProblem\Domain\Currency;
use MoneyProblem\Domain\MissingExchangeRateException;
use MoneyProblem\Domain\Money;
use MoneyProblem\Domain\Portfolio;
use PHPUnit\Framework\TestCase;

class BankBuilder
{
    /**
     * @var Currency
     */
    private Currency $pivotCurrency;
    private array $rates;

    public static function aBank(): BankBuilder
    {
        return new BankBuilder();
    }

    public function WithPivotCurrency(Currency $currency): BankBuilder
    {
        $this->pivotCurrency = $currency;
        return $this;
    }

    public function WithExchangeRate(float $rate, Currency $currency): BankBuilder
    {
        $this->rates[] = ["currency" => $currency,
            "rate" => $rate];
        return $this;
    }

    public function build(): Bank
    {
        $bank = new Bank();

        foreach ($this->rates as $exchangeRate) {
            $bank->addEchangeRate($this->pivotCurrency, $exchangeRate["currency"], $exchangeRate["rate"]);
        }
        return $bank;
    }


}

class PortfolioTest extends TestCase
{

    /**
     * @throws MissingExchangeRateException
     */
    public function test_evaluate_in_usd()
    {

        // Arrange
        $portfolio = new Portfolio();

        // Act
        $portfolio->add(new Money(10, Currency::EUR()));
        $portfolio->add(new Money(5, Currency::USD()));
        $bank = BankBuilder::aBank()->WithPivotCurrency(Currency::EUR())->WithExchangeRate(1.2, Currency::USD())->build();
        $total = $portfolio->evaluate(Currency::USD(), $bank);

        // Assert
        $this->assertEquals(new Money(17, Currency::USD()), $total);
    }

    /**
     * @throws MissingExchangeRateException
     */
    public function test_evaluate_in_kr()
    {
        // Arrange
        $portfolio = new Portfolio();

        // Act
        $portfolio->add(new Money(1, Currency::USD()));
        $portfolio->add(new Money(1100, Currency::KRW()));
        $bank = BankBuilder::aBank()->WithPivotCurrency(Currency::USD())->WithExchangeRate(1100, Currency::KRW())->build();
        $total = $portfolio->evaluate(Currency::KRW(), $bank);

        // Assert
        $this->assertEquals(new Money(2200, Currency::KRW()), $total);
    }

    /**
     * @throws MissingExchangeRateException
     */
    public function test_evaluate_in_euro()
    {

        // Arrange
        $portfolio = new Portfolio();

        // Act
        $portfolio->add(new Money(10, Currency::EUR()));
        $portfolio->add(new Money(5, Currency::USD()));
        $bank = Bank::create(Currency::USD(), Currency::EUR(), 0.82);
        $bank = BankBuilder::aBank()->WithPivotCurrency(Currency::USD())->WithExchangeRate(0.82, Currency::EUR())->build();

        $total = $portfolio->evaluate(Currency::EUR(), $bank);

        // Assert
        $this->assertEquals(new Money(14.1, Currency::EUR()), $total);

    }

    public function test_add_same_amount()
    {
        // Arrange
        $portfolio = new Portfolio();

        // Act
        $portfolio->add(new Money(10, Currency::EUR()));
        $portfolio->add(new Money(5, Currency::EUR()));
        $bank = BankBuilder::aBank()->WithPivotCurrency(Currency::EUR())->WithExchangeRate(1, Currency::EUR())->build();

        $total = $portfolio->evaluate(Currency::EUR(), $bank);

        // Assert
        $this->assertEquals(new Money(15, Currency::EUR()), $total);
    }

    /**
     * @throws MissingExchangeRateException
     */
    public function test_evaluate_multiple_amount_in_usd()
    {

        // Arrange
        $portfolio = new Portfolio();

        // Act
        $portfolio->add(new Money(10, Currency::EUR()));
        $portfolio->add(new Money(5, Currency::USD()));
        $portfolio->add(new Money(5, Currency::USD()));
        $bank = BankBuilder::aBank()->WithPivotCurrency(Currency::EUR())->WithExchangeRate(1.2, Currency::USD())->build();

        $total = $portfolio->evaluate(Currency::USD(), $bank);

        // Assert
        $this->assertEquals(new Money(22, Currency::USD()), $total);
    }

}
