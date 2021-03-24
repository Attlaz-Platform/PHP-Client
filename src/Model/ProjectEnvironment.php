<?php
declare(strict_types=1);

namespace Attlaz\Model;

class ProjectEnvironment
{
    /** @var string $id */
    public $id;
    /** @var string $key */
    public $key;
    /** @var string $name */
    public $name;
    /** @var string $projectId */
    public $projectId;
    /** @var bool $isLocal */
    public $isLocal;
}
