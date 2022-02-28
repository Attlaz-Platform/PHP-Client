<?php
declare(strict_types=1);

namespace Attlaz\Model;

class Config
{

    public string $id;
    public $inheritable;
    public $sensitive;
    public $state;
    public string $project;
    public string $projectEnvironment;
    public $key;
    public $value;
}
