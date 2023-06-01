<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mailler\CreateMaillerRequest;
use App\Http\Requests\Mailler\UpdateMaillerRequest;
use App\Http\Resources\Mailler\MaillerCollection;
use App\Http\Resources\Mailler\MaillerFormResource;
use App\Http\Resources\Mailler\MaillerResource;
use App\Models\Mailler;
use Illuminate\Http\Request;

class MaillerController extends Controller
{
    /**
     * Вывод все обработчиков
     * 
     * @return \App\Http\Resources\Mailler\MaillerCollection
     */
    public function index()
    {
        return new MaillerCollection(
            Mailler::all(),
        );
    }

    /**
     * Данные для создания обработчика рассылки
     * 
     * @return \App\Http\Resources\Mailler\MaillerFormResource
     */
    public function create()
    {
        return new MaillerFormResource(null);
    }

    /**
     * Создание нового обработчика
     * 
     * @param  \App\Http\Requests\Mailler\CreateMaillerRequest  $request
     * @return \App\Http\Resources\Mailler\MaillerResource
     */
    public function store(CreateMaillerRequest $request)
    {
        $data = $request->validated();

        return new MaillerResource(
            Mailler::create($data)->refresh()
        );
    }

    /**
     * Данные для обновления обработчика рассылки
     * 
     * @param  \App\Models\Mailler  $mailler
     * @return \App\Http\Resources\Mailler\MaillerFormResource
     */
    public function edit(Mailler $mailler)
    {
        return new MaillerFormResource($mailler);
    }

    /**
     * Обновление обработчика
     * 
     * @param  \App\Http\Requests\Mailler\UpdateMaillerRequest  $request
     * @param  \App\Models\Mailler  $mailler
     * @return \App\Http\Resources\Mailler\MaillerResource
     */
    public function update(UpdateMaillerRequest $request, Mailler $mailler)
    {
        $data = $request->validated();
        $mailler->fill($data)->save();

        return new MaillerResource(
            $mailler->refresh()
        );
    }
}
