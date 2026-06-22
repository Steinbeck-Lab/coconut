<?php

namespace App\Documentation\Schemas;

use Lomkit\Rest\Documentation\Schemas\Responses as BaseResponses;

class Responses extends BaseResponses
{
    /**
     * Preserve numeric HTTP status code keys (array_merge reindexes them to 0).
     */
    public function withOthers(array $others): Responses
    {
        $this->others = $this->others + $others;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return ['default' => $this->default()->jsonSerialize()]
            + collect($this->others())->map->jsonSerialize()->toArray();
    }
}
