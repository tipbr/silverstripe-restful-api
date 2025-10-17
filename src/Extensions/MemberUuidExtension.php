<?php

namespace TipBr\RestfulApi\Extensions;

use TipBr\RestfulApi\Traits\Uuidable;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for Member to add UUID support
 */
class MemberUuidExtension extends DataExtension
{
    use Uuidable;
}
