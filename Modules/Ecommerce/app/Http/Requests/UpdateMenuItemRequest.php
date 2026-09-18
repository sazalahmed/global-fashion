<?php

namespace Modules\Ecommerce\Http\Requests;

/**
 * Same validation surface as creating an item. The `value`/`route` whitelist
 * and URL-scheme checks in StoreMenuItemRequest::withValidator() apply here too.
 */
class UpdateMenuItemRequest extends StoreMenuItemRequest
{
}
