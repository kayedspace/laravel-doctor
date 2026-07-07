<?php

declare(strict_types=1);

namespace kayedspace\Doctor;

use kayedspace\Doctor\Domain\DoctorReport;
use kayedspace\Doctor\Domain\DoctorRequest;

class DoctorManager
{
    public function __construct(private DoctorScanAction $action) {}

    public function scan(DoctorRequest $request): DoctorReport
    {
        return $this->action->execute($request);
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function files(array $paths): DoctorReport
    {
        $request = new DoctorRequest;

        if ($paths === []) {
            return $this->scan($request->withEmptyScope());
        }

        return $this->scan($request->withPaths($paths));
    }
}
