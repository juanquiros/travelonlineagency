<?php

namespace App\Services;

use App\Entity\DriverProfile;
use App\Entity\VehicleFeature;
use App\Repository\VehicleFeatureRepository;
use Doctrine\ORM\EntityManagerInterface;

final class VehicleFeatureManager
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly VehicleFeatureRepository $featureRepository,
    ) {
    }

    /**
     * @param int[] $selectedIds
     */
    public function syncDriverFeatures(DriverProfile $driver, array $selectedIds, ?string $newFeaturesInput = null): void
    {
        $selectedIds = array_values(array_unique(array_filter(array_map(static fn ($value) => (int) $value, $selectedIds))));

        $selectedEntities = [];
        if (!empty($selectedIds)) {
            $selectedEntities = $this->featureRepository->findBy(['id' => $selectedIds]);
        }

        foreach ($driver->getVehicleFeatures() as $feature) {
            if (!in_array($feature->getId(), $selectedIds, true)) {
                $driver->removeVehicleFeature($feature);
            }
        }

        foreach ($selectedEntities as $feature) {
            if ($feature instanceof VehicleFeature && $feature->isActivo()) {
                $driver->addVehicleFeature($feature);
            }
        }

        $newFeatures = $this->parseNewFeatures($newFeaturesInput);
        foreach ($newFeatures as $nombre) {
            $feature = $this->featureRepository->findOneByCaseInsensitiveName($nombre);
            if (!$feature instanceof VehicleFeature) {
                $feature = (new VehicleFeature())
                    ->setNombre($nombre)
                    ->setActivo(true);
                $this->em->persist($feature);
            } elseif (!$feature->isActivo()) {
                $feature->setActivo(true);
            }

            $driver->addVehicleFeature($feature);
        }
    }

    /**
     * @return string[]
     */
    private function parseNewFeatures(?string $input): array
    {
        if ($input === null) {
            return [];
        }

        $chunks = preg_split('/[,;\n]+/', $input) ?: [];
        $normalised = [];
        foreach ($chunks as $chunk) {
            $value = trim($chunk);
            if ($value === '') {
                continue;
            }
            $normalised[] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        }

        return array_values(array_unique($normalised));
    }
}
