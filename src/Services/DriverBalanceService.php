<?php

namespace App\Services;

use App\Entity\CashPayment;
use App\Entity\DriverBalanceEntry;
use App\Entity\DriverProfile;
use App\Entity\DriverWithdrawalRequest;
use App\Entity\TransferAssignment;
use App\Entity\Usuario;
use App\Repository\DriverBalanceEntryRepository;
use App\Repository\DriverWithdrawalRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class DriverBalanceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DriverBalanceEntryRepository $entries,
        private readonly DriverWithdrawalRequestRepository $withdrawals,
    ) {
    }

    public function recordTransferCompletion(TransferAssignment $assignment): ?DriverBalanceEntry
    {
        if ($assignment->getEarningEntry()) {
            return $assignment->getEarningEntry();
        }

        $driver = $assignment->getChofer();
        $request = $assignment->getSolicitud();

        if (!$driver instanceof DriverProfile || !$request) {
            return null;
        }

        $total = (float) $request->getPrecioTotal();
        if ($total <= 0) {
            return null;
        }

        $commission = $driver->getCommissionPercentage();
        $driverShare = $total * max(0, min(100, 100 - $commission)) / 100;
        if ($driverShare <= 0) {
            return null;
        }

        $entry = (new DriverBalanceEntry())
            ->setDriver($driver)
            ->setAmount($driverShare)
            ->setCurrency($request->getMoneda())
            ->setDirection(DriverBalanceEntry::DIRECTION_CREDIT)
            ->setType(DriverBalanceEntry::TYPE_EARNING)
            ->setDescription(sprintf('Ingreso por traslado #%d', $request->getId() ?? 0))
            ->setReference(sprintf('transfer-earning-%d', $assignment->getId() ?? 0));

        $assignment->setEarningEntry($entry);

        $this->em->persist($entry);
        $this->em->persist($assignment);
        $this->em->flush();

        return $entry;
    }

    public function recordCashDelivery(CashPayment $payment, DriverProfile $driver, ?Usuario $actor = null): DriverBalanceEntry
    {
        $existing = $payment->getDriverBalanceEntry();
        if ($existing instanceof DriverBalanceEntry) {
            return $existing;
        }

        $entry = (new DriverBalanceEntry())
            ->setDriver($driver)
            ->setAmount($payment->getAmount())
            ->setCurrency($payment->getCurrency())
            ->setDirection(DriverBalanceEntry::DIRECTION_DEBIT)
            ->setType(DriverBalanceEntry::TYPE_CASH_DELIVERY)
            ->setDescription('Entrega de efectivo al administrador')
            ->setReference($payment->getReference());

        if ($actor instanceof Usuario) {
            $entry->setCreatedBy($actor);
        }

        $entry->setCashPayment($payment);

        $this->em->persist($entry);
        $this->em->persist($payment);
        $this->em->flush();

        return $entry;
    }

    public function removeCashDelivery(CashPayment $payment): void
    {
        $entry = $payment->getDriverBalanceEntry();
        if (!$entry instanceof DriverBalanceEntry) {
            return;
        }

        $payment->setDriverBalanceEntry(null);
        $this->em->persist($payment);
        $this->em->remove($entry);
        $this->em->flush();
    }

    public function createManualEntry(
        DriverProfile $driver,
        float $amount,
        string $currency,
        string $direction,
        string $type,
        ?string $description,
        ?string $reference,
        ?Usuario $actor = null,
    ): DriverBalanceEntry {
        $entry = (new DriverBalanceEntry())
            ->setDriver($driver)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setDirection($direction)
            ->setType($type)
            ->setDescription($description)
            ->setReference($reference);

        if ($actor instanceof Usuario) {
            $entry->setCreatedBy($actor);
        }

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    public function recordWithdrawalPayout(DriverWithdrawalRequest $request, ?Usuario $admin = null): DriverBalanceEntry
    {
        $entry = (new DriverBalanceEntry())
            ->setDriver($request->getDriver())
            ->setAmount($request->getAmount())
            ->setCurrency($request->getCurrency())
            ->setDirection(DriverBalanceEntry::DIRECTION_DEBIT)
            ->setType(DriverBalanceEntry::TYPE_WITHDRAWAL)
            ->setDescription('Retiro transferido al chofer')
            ->setReference(sprintf('withdrawal-%d', $request->getId()));

        if ($admin instanceof Usuario) {
            $entry->setCreatedBy($admin);
        }

        $this->em->persist($entry);
        $this->em->flush();

        return $entry;
    }

    /**
     * @return array{credits: float, debits: float, balance: float, pending: float, available: float}
     */
    public function buildDriverBalance(DriverProfile $driver): array
    {
        $totals = $this->entries->getTotals($driver);
        $pending = $this->withdrawals->getPendingTotal($driver);
        $available = $totals['balance'] - $pending;

        return [
            'credits' => $totals['credits'],
            'debits' => $totals['debits'],
            'balance' => $totals['balance'],
            'pending' => $pending,
            'available' => $available,
        ];
    }
}
