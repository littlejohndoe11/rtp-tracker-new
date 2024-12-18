<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Game - RTP Games Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">Edit Game: <?php echo e($game->name); ?></h1>
            <a href="<?php echo e(route('admin.games.index')); ?>" class="text-gray-600 hover:text-gray-900">
                Back to List
            </a>
        </div>

        <?php if(session('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo e(session('success')); ?>

            </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('admin.games.update', $game)); ?>" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>

            <!-- Current Image Preview -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Current Image</label>
                <?php if($game->image): ?>
                    <img src="<?php echo e(asset($game->image)); ?>" alt="<?php echo e($game->name); ?>" class="w-48 h-48 object-cover rounded mb-2">
                <?php else: ?>
                    <div class="w-48 h-48 bg-gray-200 flex items-center justify-center rounded mb-2">
                        <span class="text-gray-500">No Image</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Image Upload -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                    Upload New Image
                </label>
                <input type="file"
                       name="image"
                       id="image"
                       accept="image/jpeg,image/png,image/jpg,image/gif"
                       class="block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-full file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-blue-50 file:text-blue-700
                                  hover:file:bg-blue-100">
                <p class="text-gray-500 text-xs mt-1">Supported formats: JPG, PNG, GIF (max 2MB)</p>
            </div>

            <!-- Game Details -->
            <div class="grid grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Name
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="<?php echo e(old('name', $game->name)); ?>"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="provider_id">
                        Provider
                    </label>
                    <select name="provider_id"
                            id="provider_id"
                            class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php $__currentLoopData = $providers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $provider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($provider->id); ?>" <?php echo e(old('provider_id', $game->provider_id) == $provider->id ? 'selected' : ''); ?>>
                                <?php echo e($provider->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="theoretical_rtp">
                        Theoretical RTP (%)
                    </label>
                    <input type="number"
                           name="theoretical_rtp"
                           id="theoretical_rtp"
                           value="<?php echo e(old('theoretical_rtp', $game->theoretical_rtp)); ?>"
                           step="0.01"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="current_rtp">
                        Current RTP (%)
                    </label>
                    <input type="number"
                           name="current_rtp"
                           id="current_rtp"
                           value="<?php echo e(old('current_rtp', $game->current_rtp)); ?>"
                           step="0.01"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>

            <!-- Game Status -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Game Status</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox"
                               name="is_trending"
                               value="1"
                               <?php echo e(old('is_trending', $game->is_trending) ? 'checked' : ''); ?>

                               class="form-checkbox text-blue-600">
                        <span class="ml-2">Trending</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox"
                               name="is_popular"
                               value="1"
                               <?php echo e(old('is_popular', $game->is_popular) ? 'checked' : ''); ?>

                               class="form-checkbox text-blue-600">
                        <span class="ml-2">Popular</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="checkbox"
                               name="is_hot"
                               value="1"
                               <?php echo e(old('is_hot', $game->is_hot) ? 'checked' : ''); ?>

                               class="form-checkbox text-blue-600">
                        <span class="ml-2">Hot</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Game
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/test-project/resources/views/admin/games/edit.blade.php ENDPATH**/ ?>