<?php

namespace FullscreenInteractive\Restful\Traits;

use Ramsey\Uuid\Uuid;

trait Uuidable
{
    private static $db = [
        'UUID' => 'Varchar(200)'
    ];

    private static $indexes = [
        'UUID' => true
    ];

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->UUID) {
            $this->UUID = Uuid::uuid4()->toString();
        }
    }

    /**
     * Find by UUID
     *
     * @param string $uuid
     * @return static|null
     */
    public static function getByUUID(string $uuid)
    {
        return static::get()->filter('UUID', $uuid)->first();
    }
}
