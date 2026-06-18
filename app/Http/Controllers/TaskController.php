<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Task;

class TaskController extends Controller
{
    public function __invoke(Task $task): TaskResource
    {
        abort_unless($task->active, 404);

        return TaskResource::make($task);
    }
}
