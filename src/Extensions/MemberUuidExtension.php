<?php

namespace FullscreenInteractive\Restful\Extensions;

use FullscreenInteractive\Restful\Traits\Uuidable;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for Member to add UUID support
 */
class MemberUuidExtension extends DataExtension
{
    use Uuidable;
}
