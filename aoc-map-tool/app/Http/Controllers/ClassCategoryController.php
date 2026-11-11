<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassCategory\DestroyClassCategoryRequest;
use App\Http\Requests\ClassCategory\StoreClassCategoryRequest;
use App\Models\ClassCategory;
use App\Repositories\ClassCategory\ClassCategoryReadRepository;
use App\Repositories\ClassCategory\ClassCategoryWriteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ClassCategoryController extends Controller
{
    public function __construct(
        private ClassCategoryReadRepository $classCategoryReadRepository,
        private ClassCategoryWriteRepository $classCategoryWriteRepository,
    ) {
    }

    /**
     * List class categories.
     */
    public function index(): JsonResponse
    {
        $classCategories = $this->classCategoryReadRepository->orderedList()
            ->map(fn (ClassCategory $classCategory) => [
                'id' => $classCategory->id,
                'name' => $classCategory->name,
                'color' => $classCategory->color,
                'is_custom' => $classCategory->is_custom,
            ]);

        return response()->json(['data' => $classCategories]);
    }

    /**
     * Create a class category.
     */
    public function store(StoreClassCategoryRequest $request): JsonResponse
    {
        $classCategory = $this->classCategoryWriteRepository->create(
            $request->validated('name'),
            $request->validated('color'),
        );

        return response()->json([
            'data' => [
                'id' => $classCategory->id,
                'name' => $classCategory->name,
                'color' => $classCategory->color,
                'is_custom' => $classCategory->is_custom,
            ],
        ]);
    }

    /**
     * Delete a custom class category.
     */
    public function destroy(DestroyClassCategoryRequest $request, int $id): JsonResponse
    {
        $classCategory = $this->classCategoryReadRepository->find($id);

        if ($classCategory->is_system) {
            return response()->json(['message' => 'System class categories cannot be deleted.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$classCategory->deleteIfCustom()) {
            return response()->json(['message' => 'This class type is used by existing classes and cannot be deleted.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json(['message' => 'Class category deleted.']);
    }
}
