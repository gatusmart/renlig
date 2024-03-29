<?php

namespace App\Http\Controllers\Backend\Spa\Design;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Core\Settings\Website;
use App\Models\Core\Design\Widget;
use App\Models\Core\Design\WidgetBlock;
use App\Models\Core\Content\ContentType;

use App\Services\Themes\ThemeService;
use App\Services\WidgetService;

use App\Http\Resources\WidgetResource;
use App\Http\Resources\ThemeSettingsResource;

class WidgetController extends Controller
{
    protected $themeservice;
    protected $widgetService;

    public function __construct(ThemeService $themeservice, WidgetService $widgetService)
	{
        $this->themeservice = $themeservice;
        $this->widgetService = $widgetService;
    }
    
    public function index()
    {
        $widgets = Widget::all();
        return WidgetResource::collection($widgets);
    }

    public function getContentTree(Request $request)
    {
        $types = ContentType::with('content')->get();

        $types = $types->map(function ($object) {
            return [
                'id' => 'content_type_'.$object->id,
                'realId' => $object->id,
                'label' => $object->name,
                'type' => 'content_type',
                'children' => $object->content->map(function ($cObj) {
                    return [
                        'id' => 'content_'.$cObj->id,
                        'realId' => $cObj->id,
                        'label' => $cObj->title,
                        'type' => 'content'
                    ];
                })
            ];
        });

        return response()->json(['data' => $types]);
    }

    function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function show($id)
    {
        $areas = $this->themeservice->getActiveThemeSection('widgetAreas');

        $widget = Widget::with('blocks.settings')->where('id', $id)->first();

        // sort the widgets
        $widget->blocks = $widget->blocks->sortBy('order');

        // reset keys
        foreach ($widget->blocks as $key => $widgetBlock) {
            if($this->isJson($widgetBlock['content']))
                $widgetBlock['content'] = json_decode($widgetBlock['content'], true);
        }

        return (new WidgetResource($widget))
            ->additional(compact('areas'));
    }

    public function getAreas()
    {
        $theme = $this->themeservice->getActiveTheme();
        $areas = $this->themeservice->getSection($theme->id, 'widgetAreas');

        return ThemeSettingsResource::collection($areas);
    }

    public function store(Request $request)
    {
        $widget = $this->widgetService->save($request);

        $widget = Widget::with('blocks', 'settings')->where('id', $widget->id)->first();

        // Artisan::call('page-cache:clear', ['slug' => $widget->slug]);
        return new WidgetResource($widget);
    }

    public function destroy($id)
    {
        $content = Widget::find($id);
        $content->delete();
        return response()->json([], 200);
    }
}
