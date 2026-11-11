<?php

namespace App\Helpers;

use App\Tag;

class TagHelper
{
    public function getEnabledTags()
    {
        $tags = Tag::with('creator')
            ->where('status', 'enabled')
            ->get();

        $result = [];
        foreach ($tags as $tag) {
            $result[] = ['value' => $tag->id, 'text' => $tag->name];
        }

        return $result;
    }
}
