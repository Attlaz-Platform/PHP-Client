<?php
declare(strict_types=1);

namespace Attlaz\Model;

class AdapterConnection
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getKey(): string
    {
        return $this->data['key'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getAdapterId(): string
    {
        return $this->data['adapter'];
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getConfiguration(string $key, string $default = null): ?string
    {
        $configurations = $this->data['configuration'];
        foreach ($configurations as $configuration) {
            if ($configuration['key'] === $key) {
                return $configuration['value'];
            }
        }

        return $default;
    }

    public function setConfiguration(string $key, string $value): void
    {
        $configurations = $this->data['configuration'];
        $size = count($configurations);
        for ($i = 0; $i < $size; ++$i) {
            if ($configurations[$i]['key'] === $key) {
                $configurations[$i]['value'] = $value;

                $this->data['configuration'] = $configurations;
                return;
            }
        }


    }

    public function getConfiguratedKeys(): array
    {
        $configurations = $this->data['configuration'];

        $result = [];
        foreach ($configurations as $configuration) {
            $result[] = $configuration['key'];
        }

        return $result;
    }
}
