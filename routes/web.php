<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user()->load('roles');
        return view('app', compact('user'));
    }
    return redirect('/login');
});
Route::get('/orders', function () {
    if (Auth::check()) {
        $user = Auth::user()->load('roles');
        return view('app', compact('user'));
    }
    return redirect('/login');
});
Route::get('/products', function () {
    if (Auth::check()) {
        $user = Auth::user()->load('roles');
        return view('app', compact('user'));
    }
    return redirect('/login');
});
Route::get('/production-tasks', function () {
    if (Auth::check()) {
        $user = Auth::user()->load('roles');
        return view('app', compact('user'));
    }
    return redirect('/login');
});
Route::get('/admin', function () {
    if (Auth::check()) {
        $user = Auth::user()->load('roles');
        return view('app', compact('user'));
    }
    return redirect('/login');
});
Route::get('/documents/order', function (Request $request) {
    if (Auth::check()) {
        if ($request->has('order_id') && $request->has('direction')) {
            $currentId = $request->input('order_id');
            $direction = $request->input('direction');
            if ($direction === 'next') {
                $order = \App\Models\Order::where('id', '>', $currentId)->orderBy('id')->first();
            } else {
                $order = \App\Models\Order::where('id', '<', $currentId)->orderBy('id', 'desc')->first();
            }
            return response()->json(['order_id' => $order ? $order->id : null]);
        }
        $orderId = $request->input('order_id');
        if ($orderId) {
            $order = \App\Models\Order::with('company')->find($orderId);
        } else {
            $order = \App\Models\Order::with('company')->latest()->first();
        }
        if (!$order) {
            $order = new \App\Models\Order();
            $order->id = 1;
            $order->created_at = now();
            $order->deadline = now()->addDays(7);
            $order->status = 'wait';
            $company = new \App\Models\Company();
            $company->name = 'Демо компания';
        } else {
            $company = $order->company;
        }
        return view('documents.order', compact('order', 'company'));
    }
    return redirect('/login');
});
Route::get('/documents/task', function (Request $request) {
    if (Auth::check()) {
        if ($request->has('task_id') && $request->has('direction')) {
            $currentId = $request->input('task_id');
            $direction = $request->input('direction');
            
            if ($direction === 'next') {
                $task = \App\Models\ProductionTask::where('id', '>', $currentId)->orderBy('id')->first();
            } else {
                $task = \App\Models\ProductionTask::where('id', '<', $currentId)->orderBy('id', 'desc')->first();
            }
            
            return response()->json(['task_id' => $task ? $task->id : null]);
        }
        
        $taskId = $request->input('task_id');
        
        if ($taskId) {
            $task = \App\Models\ProductionTask::with(['order.company', 'order.product', 'components.product', 'user'])->find($taskId);
        } else {
            $task = \App\Models\ProductionTask::with(['order.company', 'order.product', 'components.product', 'user'])->latest()->first();
        }
        if (!$task) {
            $task = new \App\Models\ProductionTask();
            $task->id = 1;
            $task->created_at = now();
            $task->quantity = 10;
            $task->status = 'wait';
            $order = new \App\Models\Order();
            $order->id = 1;
            $order->deadline = now()->addDays(7);
            $order->quantity = 10;
            $company = new \App\Models\Company();
            $company->name = 'Демо компания';
            $product = new \App\Models\Product();
            $product->name = 'Демо продукт';
            $task->order = $order;
            $task->order->company = $company;
            $task->order->product = $product;
            $component1 = new \App\Models\Product();
            $component1->name = 'Сталь листовая';
            $component1->type = 'material';
            $component1->unit = 'кг';
            $component1->quantity = 2;
            $component1->used_quantity = 2;
            $component2 = new \App\Models\Product();
            $component2->name = 'Крепежные болты';
            $component2->type = 'material';
            $component2->unit = 'шт';
            $component2->quantity = 5;
            $component2->used_quantity = 3;
            $task->components = collect([$component1, $component2]);
            $master = null;
        } else {
            $order = $task->order;
            $company = $task->order->company;
            $product = $task->order->product;
            $components = $task->components;
        }
        $master = $task->user ?? null;
        return view('documents.task', compact('task', 'order', 'company', 'product', 'components', 'master'));
    }
    return redirect('/login');
});