<?php

namespace InstagramAPI\Response\Model;

use InstagramAPI\AutoPropertyMapper;

/**
 * Usertag.
 *
 * @method In[] getIn()
 * @method mixed getPhotoOfYou()
 * @method bool isIn()
 * @method bool isPhotoOfYou()
 * @method $this setIn(In[] $value)
 * @method $this setPhotoOfYou(mixed $value)
 * @method $this unsetIn()
 * @method $this unsetPhotoOfYou()
 */
class Usertag extends AutoPropertyMapper
{
    const JSON_PROPERTY_MAP = [
        'in'           => 'In[]',
        'photo_of_you' => '',
    ];
}
