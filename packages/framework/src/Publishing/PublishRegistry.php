<?php

namespace Codemonster\Annabel\Publishing;

use InvalidArgumentException;

class PublishRegistry
{
    /**
     * @var list<array{provider: class-string, source: string, destination: string, tags: list<string>}>
     */
    protected array $resources = [];

    /**
     * @param class-string $provider
     * @param array<string, string> $paths
     * @param string|list<string> $tags
     */
    public function add(string $provider, array $paths, string|array $tags = []): void
    {
        $tags = is_string($tags) ? [$tags] : $tags;

        foreach ($tags as $tag) {
            if (!is_string($tag) || $tag === '') {
                throw new InvalidArgumentException('Publish tags must be non-empty strings.');
            }
        }

        foreach ($paths as $source => $destination) {
            if (
                !is_string($source)
                || $source === ''
                || !is_string($destination)
                || $destination === ''
            ) {
                throw new InvalidArgumentException('Publish paths must map non-empty source and destination strings.');
            }

            foreach ($this->resources as $index => $resource) {
                if (
                    $resource['provider'] === $provider
                    && $resource['source'] === $source
                    && $resource['destination'] === $destination
                ) {
                    $this->resources[$index]['tags'] = array_values(array_unique(array_merge(
                        $resource['tags'],
                        $tags,
                    )));

                    continue 2;
                }
            }

            $this->resources[] = [
                'provider' => $provider,
                'source' => $source,
                'destination' => $destination,
                'tags' => array_values(array_unique($tags)),
            ];
        }
    }

    /**
     * @return list<array{provider: class-string, source: string, destination: string, tags: list<string>}>
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * @return list<array{provider: class-string, source: string, destination: string, tags: list<string>}>
     */
    public function matching(?string $provider = null, ?string $tag = null): array
    {
        return array_values(array_filter(
            $this->resources,
            static fn (array $resource): bool =>
                ($provider === null || $resource['provider'] === $provider)
                && ($tag === null || in_array($tag, $resource['tags'], true)),
        ));
    }
}
