<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Config
{
    /** @var string $id */
    public $id;
    public $inheritable;
    public $sensitive;
    public $state;
    /** @var string $project */
    public $project;
    /** @var string $projectEnvironment */
    public $projectEnvironment;
    public $key;
    public $value;
}
