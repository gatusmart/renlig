<?php

namespace App\Models\Core\Content;

use Illuminate\Database\Eloquent\Model;

use App\Models\Core\Settings\HasSettings;
use Auth;

class ContentBlock extends Model
{
    use HasSettings;

    protected $table = "content_blocks";

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    public function subBlocks()
    {
        return $this->hasMany(self::class, 'parent_id', 'unique_id');
    }

    public static function boot()
    {
       parent::boot();

       static::deleting(function ($model) {
           $model->settings()->delete();
           $model->settings()->sync([]);
           foreach ($model->subBlocks as $key => $subBlock) {
               $subBlock->delete();
               self::recursiveDelete($subBlock);
           }
       });
    }

    // return block settings with defaults...
    public function getSettings()
    {
        $settings = array();
        if (isset($this->type)) {
            $probablePaths = [
                'App\\Models\\Core\\Content\\Block\\' . studly_case($this->type) . 'Block',
                'App\\Models\\Core\\Content\\ThirdPartyBlocks\\' . studly_case($this->type) . 'Block'
            ];

            foreach ($probablePaths as $path) {
                if (!class_exists($path)) continue;
                $block = new $path;
            }

            if (method_exists($block, 'getDefaults')) {
                $settings = $block->getDefaults();
            }
        }

        foreach ($this->settings as $key => $setting) {
            $settings[$setting['key']] = $setting['value'];
        }

        return (object)$settings;
    }

    static public function set($content_id, $blockData, $parentId)
    {
        $block = ContentBlock::where('unique_id', $blockData->uniqueId)->where('content_id', $content_id)->first();

        if($block) {
            $block->content_id = $content_id;

            $block->order = $blockData->order;
            $block->unique_id = $blockData->uniqueId;
            $block->parent_id = $parentId;
            $block->user_id = Auth::check() ? Auth::user()->id : 1;

            if(isset($blockData->content)) {
                if(is_array($blockData->content))
                    $block->content = json_encode($blockData->content);
                else
                    $block->content = $blockData->content;
            }

            if(isset($blockData->templateBlockId)) {
                $block->tblock_id = $blockData->templateBlockId;
            }

            $block->update();
        } else {
            $block = new ContentBlock();
            $block->content_id = $content_id;
            $block->type = $blockData->type;
            $block->order = $blockData->order;
            $block->unique_id = $blockData->uniqueId;
            $block->parent_id = $parentId;

            $block->user_id = Auth::check() ? Auth::user()->id : 1;

            if(isset($blockData->content)) {
                if(is_array($blockData->content))
                    $block->content = json_encode($blockData->content);
                else
                    $block->content = $blockData->content;
            }

            if(isset($blockData->templateBlockId)) {
                $block->tblock_id = $blockData->templateBlockId;
            }

            $block->save();
        }

        return $block;
    }

    public static function recursiveDelete($subBlock) {
        foreach ($subBlock->subBlocks as $key => $model) {
            $model->delete();
            self::recursiveDelete($model);
        }
    }
}
