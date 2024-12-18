<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RTP Games Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Manage Games</h1>
    </div>

    <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead>
            <tr class="bg-gray-50">
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Image
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Provider
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Current RTP
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php $__currentLoopData = $games; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr x-data="{ showUpload: false }">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="relative group">
                            <!-- Current Image or Placeholder -->
                            <div class="w-16 h-16 rounded overflow-hidden">
                                <?php if($game->image): ?>
                                    <img src="<?php echo e(asset($game->image)); ?>" alt="<?php echo e($game->name); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-xs text-gray-500">No Image</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Edit Image Button -->
                            <button
                                @click="showUpload = true"
                                class="absolute inset-0 bg-black bg-opacity-50 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-xs">
                                Edit Image
                            </button>
                        </div>

                        <!-- Image Upload Form -->
                        <div x-show="showUpload"
                             @click.away="showUpload = false"
                             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full" @click.stop>
                                <h3 class="text-lg font-bold mb-4">Update Image for <?php echo e($game->name); ?></h3>

                                <form action="<?php echo e(route('admin.games.update-image', $game)); ?>"
                                      method="POST"
                                      enctype="multipart/form-data">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('POST'); ?>

                                    <div class="mb-4">
                                        <input type="file"
                                               name="image"
                                               accept="image/*"
                                               class="block w-full text-sm text-gray-500
                                                              file:mr-4 file:py-2 file:px-4
                                                              file:rounded-full file:border-0
                                                              file:text-sm file:font-semibold
                                                              file:bg-blue-50 file:text-blue-700
                                                              hover:file:bg-blue-100"
                                               required>
                                    </div>

                                    <div class="flex justify-end space-x-3">
                                        <button type="button"
                                                @click="showUpload = false"
                                                class="px-4 py-2 text-gray-600 hover:text-gray-800">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Upload
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <?php echo e($game->name); ?>

                    </td>
                    <td class="px-6 py-4">
                        <?php echo e($game->provider->name); ?>

                    </td>
                    <td class="px-6 py-4">
                                <span class="px-2 py-1 text-sm <?php echo e($game->current_rtp >= $game->theoretical_rtp ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?> rounded-full">
                                    <?php echo e(number_format($game->current_rtp, 2)); ?>%
                                </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="<?php echo e(route('admin.games.edit', $game)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            Edit Details
                        </a>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <?php echo e($games->links()); ?>

    </div>
</div>
</body>
</html>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/test-project/resources/views/admin/games/index.blade.php ENDPATH**/ ?>