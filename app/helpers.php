<?php

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mtownsend\XmlToArray\XmlToArray;
use Ramsey\Uuid\Uuid;
use Spatie\ArrayToXml\ArrayToXml;

if (!function_exists('get_file_url')) {
    function get_file_url($folderName, $fileName)
    {
        return sprintf(
            '%s/%s/%s',
            config('media.url'),
            $folderName,
            $fileName
        );
    }
}

if (!function_exists('get_file_source')) {
    function get_file_source($folderName, $fileName)
    {
        return sprintf(
            '%s/%s/%s',
            config('media.path'),
            $folderName,
            $fileName
        );
    }
}

if (!function_exists('get_x_platform')) {
    function get_x_platform()
    {
        //Temporary
        if (app()->environment('testing')) {
            return 'web';
        }

        return isset($_SERVER['HTTP_X_PLATFORM']) ? strtolower($_SERVER['HTTP_X_PLATFORM']) : null;
    }
}

if (! function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('obj_to_arr')) {
    function obj_to_arr($obj)
    {
        return json_decode(json_encode($obj), true);
    }
}

if (!function_exists('now')) {
    function now()
    {
        return Carbon::now();
    }
}

if (!function_exists('public_path')) {
   /**
    * Get the path to the public folder.
    *
    * @param string $path
    * @return string
    */
    function public_path($path = '')
    {
        return env('PUBLIC_PATH', base_path('public')) . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('get_arr_one_dimen')) {
    /** @return string */
    function get_arr_one_dimen(array $data)
    {
        return call_user_func_array('array_merge', array_values($data));
    }
}

if (!function_exists('check_include')) {
    /** @return string */
    function check_include(Request $request, $value)
    {
        $includes = explode(',', $request->query('include'));

        return in_array($value, $includes, true);
    }
}

if (!function_exists('convert_date_from_ax')) {
    /** @return string */
    function convert_date_from_ax($date)
    {
        return Carbon::createFromFormat('m/d/Y', $date)->toDateString();
    }
}

if (!function_exists('is_uuid')) {
    function is_uuid($value)
    {
        if (!$value) {
            return false;
        }

        return Uuid::isValid($value);
    }
}

if (!function_exists('arr_to_xml')) {
    function arr_to_xml($arr, $root = 'Request')
    {
        return ArrayToXml::convert($arr, $root);
    }
}

if (!function_exists('xml_to_arr')) {
    function xml_to_arr($xml)
    {
        return XmlToArray::convert($xml);
    }
}
