<?php

namespace App\Services\UnbilledScheduleOutcome;

use App\ScheduleMaster;
use App\Services\UnbilledScheduleOutcome\Handlers\Handler;
use App\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class UnbilledScheduleOutcomeService
{
    private const THOUGHTFUL_AI_PROCESS_NAME = 'thoughtful_ai';

    private ScheduleMaster $scheduleMasterModel;
    private User $userModel;
    private array $handlers;

    /**
     * @param ScheduleMaster $scheduleMasterModel
     * @param User $userModel
     * @param array $handlers
     */
    public function __construct(
        ScheduleMaster $scheduleMasterModel,
        User $userModel,
        array $handlers
    ) {
        $this->scheduleMasterModel = $scheduleMasterModel;
        $this->userModel = $userModel;
        $this->handlers = $handlers;
    }

    /**
     * @return void
     * @throws Throwable
     */
    public function process(): void
    {
        $lastProcessedId = $this->getLastProcessedId();
        $data = $this->scheduleMasterModel->getLatestAINotes($lastProcessedId);
        $user = $this->userModel->getAIUser();
        $currentLastProcessedId = $data->max('id') ?? 0;
        $data = $data->toArray();

        DB::beginTransaction();

        try {
            foreach ($data as $datum) {
                $ids = $this->extractIds($datum['sm_ids']);

                if (empty($ids)) {
                    continue;
                }

                /** @var Handler $handler */
                foreach ($this->handlers as $handler) {
                    if ($handler->supports($datum['notes'])) {
                        $handler->handle($ids, $datum, $user);
                    }
                }
            }

            if ($lastProcessedId !== $currentLastProcessedId && $currentLastProcessedId !== 0) {
                $this->setLastProcessedId($currentLastProcessedId);
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
        }
    }

    /**
     * @param string $ids
     * @return array
     */
    private function extractIds(string $ids): array
    {
        return array_map('intval', array_filter(explode(',', $ids)));
    }

    /**
     * @return int
     */
    private function getLastProcessedId(): int
    {
        return DB::table('process_settings')->where('process', self::THOUGHTFUL_AI_PROCESS_NAME)->value('last_updated_id');
    }

    /**
     * @param int $id
     * @return void
     */
    private function setLastProcessedId(int $id): void
    {
        DB::table('process_settings')
            ->where('process', self::THOUGHTFUL_AI_PROCESS_NAME)
            ->update(['last_updated_id' => $id, 'last_updated_date' => now()]);
    }
}
