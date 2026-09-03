<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\Department;
use Yii;
use api\components\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class DepartmentController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * GET /api/v1/departments
     */
    public function actionIndex(): array
    {
        $query = Department::find()
            ->orderBy([
                'location_id' => SORT_ASC,
                'parent_id' => SORT_ASC,
                'name' => SORT_ASC,
            ]);

        $locationId = Yii::$app->request->get('location_id');

        if ($locationId !== null && $locationId !== '') {
            $query->andWhere([
                'location_id' => (int) $locationId,
            ]);
        }

        $departments = $query->all();

        return [
            'success' => true,
            'data' => array_map(
                static fn (Department $department): array =>
                    self::serializeDepartment($department),
                $departments
            ),
        ];
    }

    /**
     * GET /api/v1/departments/{id}
     */
    public function actionView(int $id): array
    {
        $department = Department::findOne($id);

        if ($department === null) {
            throw new NotFoundHttpException(
                'Department not found.'
            );
        }

        return [
            'success' => true,
            'data' => self::serializeDepartment($department),
        ];
    }

    /**
     * POST /api/v1/departments
     */
    public function actionCreate(): array
    {
        $body = Yii::$app->request->bodyParams;

        $department = new Department();

        $department->location_id = (int) ($body['location_id'] ?? 0);
        $department->name = trim((string) ($body['name'] ?? ''));
        $department->code = trim((string) ($body['code'] ?? ''));
        $department->parent_id = isset($body['parent_id'])
            ? (int) $body['parent_id']
            : null;
        $department->status = isset($body['status'])
            ? (int) $body['status']
            : Department::STATUS_ACTIVE;

        if (!$department->validate()) {
            throw new BadRequestHttpException(
                implode(' ', $department->getErrorSummary(true))
            );
        }

        if ($department->parent_id !== null) {
            $parent = Department::findOne($department->parent_id);

            if ($parent === null) {
                throw new BadRequestHttpException(
                    'Parent department not found.'
                );
            }

            if ($parent->location_id !== $department->location_id) {
                throw new BadRequestHttpException(
                    'Parent department must belong to the same location.'
                );
            }
        }

        $now = date('Y-m-d H:i:s');

        $department->created_at = $now;
        $department->updated_at = $now;

        if (!$department->save(false)) {
            throw new BadRequestHttpException(
                'Unable to create department.'
            );
        }

        return [
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => self::serializeDepartment($department),
        ];
    }

    /**
     * PUT /api/v1/departments/{id}
     */
    public function actionUpdate(int $id): array
    {
        $department = Department::findOne($id);

        if ($department === null) {
            throw new NotFoundHttpException(
                'Department not found.'
            );
        }

        $body = Yii::$app->request->bodyParams;

        if (array_key_exists('location_id', $body)) {
            $department->location_id = (int) $body['location_id'];
        }

        if (array_key_exists('name', $body)) {
            $department->name = trim((string) $body['name']);
        }

        if (array_key_exists('code', $body)) {
            $department->code = trim((string) $body['code']);
        }

        if (array_key_exists('parent_id', $body)) {
            $department->parent_id = $body['parent_id'] === null
                ? null
                : (int) $body['parent_id'];
        }

        if (array_key_exists('status', $body)) {
            $department->status = (int) $body['status'];
        }

        if ($department->parent_id === $department->id) {
            throw new BadRequestHttpException(
                'A department cannot be its own parent.'
            );
        }

        if ($department->parent_id !== null) {
            $parent = Department::findOne($department->parent_id);

            if ($parent === null) {
                throw new BadRequestHttpException(
                    'Parent department not found.'
                );
            }

            if ($parent->location_id !== $department->location_id) {
                throw new BadRequestHttpException(
                    'Parent department must belong to the same location.'
                );
            }
        }

        if (!$department->validate()) {
            throw new BadRequestHttpException(
                implode(' ', $department->getErrorSummary(true))
            );
        }

        $department->updated_at = date('Y-m-d H:i:s');

        if (!$department->save(false)) {
            throw new BadRequestHttpException(
                'Unable to update department.'
            );
        }

        return [
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => self::serializeDepartment($department),
        ];
    }

    /**
     * DELETE /api/v1/departments/{id}
     */
    public function actionDelete(int $id): array
    {
        $department = Department::findOne($id);

        if ($department === null) {
            throw new NotFoundHttpException(
                'Department not found.'
            );
        }

        if ($department->getChildren()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a department that has child departments.'
            );
        }

        if ($department->getUsers()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a department that has users.'
            );
        }

        if ($department->getFolders()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a department that has folders.'
            );
        }

        if ($department->getFiles()->exists()) {
            throw new BadRequestHttpException(
                'Cannot delete a department that has files.'
            );
        }

        if ($department->delete() === false) {
            throw new BadRequestHttpException(
                'Unable to delete department.'
            );
        }

        return [
            'success' => true,
            'message' => 'Department deleted successfully.',
        ];
    }

    private static function serializeDepartment(
        Department $department
    ): array {
        return [
            'id' => (int) $department->id,
            'location_id' => (int) $department->location_id,
            'name' => $department->name,
            'code' => $department->code,
            'parent_id' => $department->parent_id !== null
                ? (int) $department->parent_id
                : null,
            'status' => (int) $department->status,
            'created_at' => $department->created_at,
            'updated_at' => $department->updated_at,
        ];
    }
}
