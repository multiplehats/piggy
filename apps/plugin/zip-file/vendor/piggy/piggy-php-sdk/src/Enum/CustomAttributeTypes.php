<?php

namespace Piggy\Api\Enum;

use MabeEnum\Enum;

class CustomAttributeTypes extends Enum
{
    const URL = 'url';

    const TEXT = 'text';

    const DATE = 'date';

    const PHONE = 'phone';

    const FLOAT = 'float';

    const COLOR = 'color';

    const EMAIL = 'email';

    const NUMBER = 'number';

    const SELECT = 'select';

    const BOOLEAN = 'boolean';

    const RICH_TEXT = 'rich_text';

    const DATE_TIME = 'date_time';

    const LONG_TEXT = 'long_text';

    const DATE_RANGE = 'date_range';

    const TIME_RANGE = 'time_range';

    const IDENTIFIER = 'identifier';

    const BIRTH_DATE = 'birth_date';

    const FILE_UPLOAD = 'file_upload';

    const MEDIA_UPLOAD = 'media_upload';

    const MULTI_SELECT = 'multi_select';

    const LICENSE_PLATE = 'license_plate';
}
