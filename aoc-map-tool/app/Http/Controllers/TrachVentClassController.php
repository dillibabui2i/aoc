<?php

namespace App\Http\Controllers;

use App\Exceptions\TrachVentClassScheduleException;
use App\Http\Requests\TrachVentClass\GridRequest;
use App\Http\Requests\TrachVentClass\ScheduleTrachVentClassRequest;
use App\Http\Requests\TrachVentClass\UpdateTrachVentClassRequest;
use App\Repositories\TrachVentClass\TrachVentClassReadRepository;
use App\Repositories\User\UserReadRepository;
use App\Services\TrachVentClass\TrachVentClassScheduler;
use App\Services\TrachVentClass\TrachVentClassUpdater;
use App\Repositories\ClassCategory\ClassCategoryReadRepository;
use Illuminate\Http\RedirectResponse;
use App\Helpers\H;
use Illuminate\View\View;

class TrachVentClassController extends Controller
{
    public function __construct(
        private UserReadRepository $userReadRepository,
        private ClassCategoryReadRepository $classCategoryReadRepository,
        private TrachVentClassScheduler $trachVentClassScheduler,
        private TrachVentClassUpdater $trachVentClassUpdater,
        private TrachVentClassReadRepository $trachVentClassReadRepository,
    ) {
    }

    /**
     * Show the Class list.
     */
    public function index(): View
    {
        $canManageClassTypes = H::canPermissions('managetrachventclass.class-categories.index');

        return view('trachventclass.grid', [
            'canManageClassTypes' => $canManageClassTypes,
        ]);
    }

    /**
     * Show the Class ajax list.
     */
    public function getTrachVentClassList(GridRequest $request): array
    {
        $dto = $request->toDto();
        $branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $columns = [
            0 => 'class_categories.name',
            3 => 'trach_vent_classes.class_date',
            4 => 'trach_vent_classes.class_time',
            7 => 'trach_vent_classes.capacity',
        ];
        $trachVentClassList = $this->trachVentClassReadRepository->forGridQuery(
            $dto->searchValue
        );
        if ($dto->orderColumn !== null && $dto->orderDirection !== null) {
            if (isset($columns[$dto->orderColumn])) {
                $trachVentClassList = $trachVentClassList->orderBy($columns[$dto->orderColumn], $dto->orderDirection);
            }
        }
        $total_records = $trachVentClassList->get();

        if ($dto->start !== null && $dto->length !== null && $dto->length !== -1) {
            $trachVentClassList = $trachVentClassList->offset($dto->start)->limit($dto->length);
        }

        $trachVentClassList = $trachVentClassList->get()->toArray();
        $instructorIds = array_filter(array_unique(array_column($trachVentClassList, 'instructor_id')));
        $instructors = $this->userReadRepository->getInstructorsByIds($instructorIds)
            ->pluck('name', 'id')
            ->toArray();

        $cnt = 0;
        foreach ($trachVentClassList as $trachVentClassData) {
            $trachVentClassList[$cnt]['type_name'] = $trachVentClassData['class_category_name'] ?? '';
            $trachVentClassList[$cnt]['display_class_date'] = H::getFormatedDate($trachVentClassData['class_date']);
            $trachVentClassList[$cnt]['display_class_time'] = getFormatedTimeRange($trachVentClassData['class_time'], $trachVentClassData['end_time'] ?? null);
            $trachVentClassList[$cnt]['display_repeat_until'] = H::getFormatedDate($trachVentClassData['repeat_until'] ?? '');

            foreach ($branchLocation as $b) {
                if ((int) $b['id'] === (int) $trachVentClassData['location_id']) {
                    $trachVentClassList[$cnt]['location'] =  $b['branch'];
                    break;
                }
            }

            $trachVentClassList[$cnt]['instructor_name'] = $instructors[$trachVentClassData['instructor_id']] ?? '';

            $btn = '';
            $btn .= '<a href="' . url('/managetrachventclass/edit', $trachVentClassData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            $trachVentClassList[$cnt]['action'] = $btn;
            $cnt++;
        }
        $data = [
            'draw'            => $dto->draw,
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $trachVentClassList,
        ];
        return $data;
    }

    /**
     * Class Add.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function add(): View
    {
        $classCategories = $this->classCategoryReadRepository->orderedList()->pluck('name', 'id');
        return view('trachventclass.scheduleclassform', [
            'instructorList' => $this->userReadRepository->getActiveInstructors()->toArray(),
            'classCategories' => $classCategories,
        ]);
    }

    public function saveScheduleClass(ScheduleTrachVentClassRequest $request): RedirectResponse
    {
        try {
            $this->trachVentClassScheduler->generate($request->toDTO());

            return redirect('/managetrachventclass')->with('success', 'Class Scheduled successfully!');
        } catch (\Exception $ex) {
            return redirect('/managetrachventclass/add/')->with('error', 'Something went wrong.');
        }
    }

    public function edit(int $id): View
    {
        $classDetail = $this->trachVentClassReadRepository->find($id);
        $classCategories = $this->classCategoryReadRepository->orderedList()->pluck('name', 'id');

        return view('trachventclass.scheduleclassform', [
            'classDetail' => $classDetail,
            'instructorList' => $this->userReadRepository->getActiveInstructors()->toArray(),
            'classCategories' => $classCategories,
        ]);
    }

    public function updateScheduleClass(UpdateTrachVentClassRequest $request): RedirectResponse
    {
        $dto = $request->toDTO();

        try {
            $this->trachVentClassUpdater->update($dto);

            return redirect('/managetrachventclass')->with('success', 'Class updated!');
        } catch (TrachVentClassScheduleException $e) {
            return redirect('/managetrachventclass/edit/' . $dto->classId)->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect('/managetrachventclass/edit/' . $dto->classId)->with('error', 'Something went wrong.');
        }
    }
}
