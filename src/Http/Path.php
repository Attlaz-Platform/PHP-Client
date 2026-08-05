<?php
declare(strict_types=1);

namespace Attlaz\Http;

/**
 * Build a request path from a template, encoding every value that goes into it.
 *
 *     Path::build('/data-quality/datasets/:datasetId/scans', ['datasetId' => $datasetId])
 *
 * Ids are opaque strings supplied by the caller. Concatenating them straight into a URL means a
 * value containing `/`, `?` or `#` silently changes which endpoint is called; an empty one produces
 * a path that quietly addresses the collection instead of the item. Encoding here makes the request
 * mean what it says, and an empty value an error rather than a surprise.
 */
class Path
{
    /**
     * @param array<string,string|int> $parameters
     */
    public static function build(string $template, array $parameters = []): string
    {
        $used = [];

        $built = \preg_replace_callback(
            '/:([A-Za-z][A-Za-z0-9_]*)/',
            static function (array $match) use ($template, $parameters, &$used): string {
                $name = $match[1];
                if (!\array_key_exists($name, $parameters)) {
                    throw new \InvalidArgumentException('Unable to build path "' . $template . '": no value for ":' . $name . '"');
                }
                $used[$name] = true;

                $value = (string)$parameters[$name];
                if ($value === '') {
                    throw new \InvalidArgumentException('Unable to build path "' . $template . '": ":' . $name . '" is empty');
                }

                return \rawurlencode($value);
            },
            $template,
        );

        if ($built === null) {
            throw new \InvalidArgumentException('Unable to build path "' . $template . '"');
        }

        // A value with no matching placeholder is a typo that would otherwise vanish silently.
        foreach (\array_keys($parameters) as $name) {
            if (!isset($used[$name])) {
                throw new \InvalidArgumentException('Unable to build path "' . $template . '": no placeholder for "' . $name . '"');
            }
        }

        return $built;
    }
}
