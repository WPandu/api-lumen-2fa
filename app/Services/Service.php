<?php

namespace App\Services;

use App\Facades\Image;
use App\Http\Resources\ResourceCollection;
use App\Jobs\RecordLog;
use Auth;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

abstract class Service
{
    protected const ROOT_PROPERTY = 'data';

    protected const REGEX_ALPHA = '/^[A-Za-z\s]+$/';

    protected const REGEX_ALPHA_NUMERIC = '/^[\w., ]+$/';

    protected const REGEX_PHONE = '/^(^\+62|62|^08)(\d{3,4}-?){2}\d{3,4}$/';

    protected const REGEX_PASSWORD = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';

    private const DURATION_CACHE = 60;

    private $model;

    abstract protected function filter($model, $request);

    abstract protected function sorting($model, $request);

    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * For Get ALL from DB
     *
     * @return array
     */
    protected function listPaginate(Request $request)
    {
        //Using Cache
        if ($request->get('using_cache') && Cache::has($this->cacheKey($request))) {
            return Cache::get($this->cacheKey($request));
        }

        $perPage = $request->get('perpage') ?: $this->model->perPage;

        $paginator = $this->filter($this->model->query(), $request);

        if ($request->get('filter') === 'active') {
            $paginator = $paginator->active();
        }

        $paginator = $this->sorting($paginator, $request);

        $paginator = $paginator->paginate($perPage);
        $result = new ResourceCollection($paginator);

        //Using Cache
        if ($request->get('using_cache')) {
            Cache::put($this->cacheKey($request), $result, self::DURATION_CACHE);
        }

        return $result;
    }

    /**
     * For Insert to DB
     *
     * @return bool
     */
    protected function insert(array $data)
    {
        if (Schema::hasColumns($this->model->getTable(), ['created_by', 'updated_by'])) {
            $data['created_by'] = Auth::user()->id ?? null;
            $data['updated_by'] = Auth::user()->id ?? null;
        }

        //Insert Log
        $this->insertLog('create', $data);

        //Insert Data
        return $this->model->create($data);
    }

    /**
     * For Update to DB
     *
     * @return bool
     */
    protected function update($id, array $data)
    {
        if (Schema::hasColumns($this->model->getTable(), ['updated_by'])) {
            $data['updated_by'] = Auth::user()->id ?? null;
        }

        //Insert Log
        $this->insertLog('update', $data);

        //Update Data
        $obj = $this->model->findOrFail($id);
        $obj->update($data);

        return $obj->refresh();
    }

    /**
     * For Delete from DB
     *
     * @return bool
     */
    protected function delete($id)
    {
        $obj = $this->model->findOrFail($id);

        //Insert Log
        $this->insertLog('delete', $obj->toArray());

        return $obj->delete();
    }

    /**
     * For Get Detail from DB
     *
     * @param $identifier (can id or slug)
     * @return array
     */
    protected function detail($identifier, $otherIdentifier = 'slug')
    {
        //phpcs:ignore SlevomatCodingStandard.ControlStructures.RequireTernaryOperator.TernaryOperatorNotUsed
        if (is_uuid($identifier) || !Schema::hasColumns($this->model->getTable(), [$otherIdentifier])) {
            $obj = $this->model->findOrFail($identifier);
        } else {
            $obj = $this->model->where($otherIdentifier, $identifier)->firstOrFail();
        }

        //phpcs::ignore
        $transformer = Str::replaceFirst('App\Models', 'App\Http\Resources', get_class($obj));

        return [
            'data' => new $transformer($obj),
        ];
    }

    /**
     * For Validation param
     *
     * @param $rules
     * @return \Illuminate\Validation\Validator
     */
    protected function validation(array $request, array $rules, array $messages = [])
    {
        return Validator::make($request, $rules, $messages);
    }

    /**
     * For Upload File
     *
     * @param $rules
     * @return string | null
     */
    protected function uploadFile(Request $request, $postName, $folderName)
    {
        try {
            if ($request->hasFile($postName)) {
                $path = sprintf(
                    '%s/%s',
                    config('media.path'),
                    $folderName
                );

                $fileName = sprintf(
                    '%s%s.%s',
                    Str::random(6),
                    time(),
                    $request->file($postName)->getClientOriginalExtension()
                );
                $request->file($postName)->move($path, $fileName);

                return $fileName;
            }

            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * For Upload Image
     *
     * @param $rules
     * @return string | null
     */
    protected function uploadImage($imageData, $folderName)
    {
        try {
            $fileName = sprintf(
                '%s%s.jpg',
                Str::random(6),
                time()
            );

            $path = sprintf('%s/%s', $folderName, $fileName);

            Storage::disk('media')->put(
                $path,
                (string)Image::make($imageData)->encode()
            );

            return $fileName;
        } catch (Exception $e) {
            return null;
        }
    }

    protected function uploadFileBase64(
        Request $request,
        $postName,
        $folderName,
        $extensionAllowed = [],
        $maximumFilesize = 1000
    ) {
        if (!$request->input($postName)) {
            return null;
        }

        $content = base64_decode($request->input($postName), true);
        $extension = null;

        if (strpos($content, 'PDF') !== false) {
            $extension = 'pdf';
        } elseif (strpos($content, 'workbook') !== false) {
            $extension = 'xlsx';
        } elseif (strpos($content, 'themeManager.xml') !== false) {
            $extension = 'xls';
        } elseif (strpos($content, 'document.xml.rels') !== false) {
            $extension = 'docx';
        } elseif (strpos($content, 'Word.Document') !== false) {
            $extension = 'doc';
        } elseif (strpos($content, 'JFIF') !== false) {
            $extension = 'jpg';
        } elseif (strpos($content, 'ASCII') !== false ||
            strpos($content, 'STLB') !== false ||
            strpos($content, 'STL') !== false
        ) {
            $extension = 'stl';
        } elseif (strpos($content, '3dmodel') !== false) {
            $extension = '3mf';
        }

        if (is_null($extension)) {
            throw new Exception($postName . ' file extension undetect');
        }

        if (!in_array($extension, $extensionAllowed, true)) {
            throw new Exception($postName . ' file extension not allowed');
        }

        $path = sprintf(
            '%s/%s',
            config('media.path'),
            $folderName
        );

        if (!file_exists($path)) {
            mkdir($path);
        }

        $fileName = sprintf(
            '%s%s.%s',
            Str::random(6),
            time(),
            $extension
        );

        $fullPath = $path . '/' . $fileName;

        file_put_contents($fullPath, $content);

        $fileSize = filesize($fullPath) / 1000;

        if ($fileSize > $maximumFilesize) {
            shell_exec('rm ' . $fullPath);

            throw new Exception($postName . ' maximum file only ' . $maximumFilesize . 'kb');
        }

        return $fileName;
    }

    /**
     * For Delete File
     *
     * @param $rules
     * @return boolean
     */
    protected function deleteFile($folderName, $fileName)
    {
        try {
            $target = sprintf(
                '%s/%s/%s',
                config('media.path'),
                $folderName,
                $fileName
            );

            shell_exec('rm ' . $target);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    protected function cacheKey(Request $request)
    {
        return sprintf(
            '%s_platform_%s_%s_%s',
            $this->model->getTable(),
            get_platform_id() ?: 0,
            implode('_', array_keys($request->all())),
            implode('_', $request->all())
        );
    }

    protected function insertLog($type, array $data)
    {
        if (env('RECORD_LOG', false)) {
            $param = [
                'type' => $type,
                'title' => $this->getTitle(),
                'table_name' => $this->getTableName(),
                'data' => $data,
            ];

            dispatch(new RecordLog($param));
        }
    }

    private function getTableName()
    {
        return env('DB_DATABASE') . '.' . $this->model->getTable();
    }

    private function getTitle()
    {
        $className = class_basename($this->model);
        $arr = preg_split('/(?=[A-Z])/', $className);
        $result = implode(' ', $arr);

        return trim($result);
    }
}
