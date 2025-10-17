<?php

namespace FullscreenInteractive\Restful\Interfaces;

interface ApiReadable
{
    /**
     * Convert object to API representation
     *
     * @param array $context Context for serialization (e.g., 'fields', 'include', 'permissions')
     * @return array
     */
    public function toApi(array $context = []): array;
}
