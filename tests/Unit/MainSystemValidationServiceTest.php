<?php

namespace Tests\Unit;

use App\Models\MainSystem;
use App\Services\MainSystemValidationService;
use Tests\TestCase;

class MainSystemValidationServiceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private MainSystemValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = new MainSystemValidationService();
    }

}
