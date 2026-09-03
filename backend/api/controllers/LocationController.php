<?php

declare(strict_types=1);

namespace api\controllers;

use api\components\HttpBearerAuth;
use common\models\Location;
use common\models\User;
use Yii;
use yii\rest\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class LocationController extends Controller
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
     * GET /api/v1/locations
     */
    public function actionIndex(): array
    {
        $locations = Location::find()
            ->where([
                'status' => Location::STATUS_ACTIVE,
            ])
            ->orderBy([
                'country' => SORT_ASC,
                'name' => SORT_ASC,
            ])
            ->all();

        return [
            'success' => true,
            'data' => array_map(
                static fn (Location $location): array => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'code' => $location->code,
                    'country' => $location->country,
                    'parent_id' => $location->parent_id,
                    'status' => $location->status,
                    'created_at' => $location->created_at,
                    'updated_at' => $location->updated_at,
                ],
                $locations
            ),
        ];
    }

    /**
     * GET /api/v1/locations/{id}
     */
    public function actionView(int $id): array
    {
        $location = $this->findLocation($id);

        return [
            'success' => true,
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'country' => $location->country,
                'parent_id' => $location->parent_id,
                'status' => $location->status,
                'created_at' => $location->created_at,
                'updated_at' => $location->updated_at,
            ],
        ];
    }

    /**
     * POST /api/v1/locations
     */
    public function actionCreate(): array
    {
        /** @var User|null $user */
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new ForbiddenHttpException(
                'Authentication required.'
            );
        }

        /*
         * For the MVP, organization-wide roles may manage locations.
         */
        if (!$user->hasGlobalAccess()) {
            throw new ForbiddenHttpException(
                'You are not allowed to create locations.'
            );
        }

        $body = Yii::$app->request->bodyParams;

        $name = trim((string) ($body['name'] ?? ''));
        $code = strtoupper(trim((string) ($body['code'] ?? '')));
        $country = trim((string) ($body['country'] ?? ''));

        $parentId = $body['parent_id'] ?? null;

        if ($name === '' || $code === '' || $country === '') {
            throw new BadRequestHttpException(
                'Name, code and country are required.'
            );
        }

        if (
            Location::find()
                ->where(['code' => $code])
                ->exists()
        ) {
            throw new BadRequestHttpException(
                'Location code already exists.'
            );
        }

        if ($parentId !== null) {
            $parentId = (int) $parentId;

            if (
                !Location::find()
                    ->where(['id' => $parentId])
                    ->exists()
            ) {
                throw new BadRequestHttpException(
                    'Parent location does not exist.'
                );
            }
        }

        $location = new Location();

        $location->name = $name;
        $location->code = $code;
        $location->country = $country;
        $location->parent_id = $parentId;
        $location->status = Location::STATUS_ACTIVE;

        $now = date('Y-m-d H:i:s');

        $location->created_at = $now;
        $location->updated_at = $now;

        if (!$location->save()) {
            return [
                'success' => false,
                'errors' => $location->getErrors(),
            ];
        }

        Yii::$app->response->statusCode = 201;

        return [
            'success' => true,
            'message' => 'Location created successfully.',
            'data' => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'country' => $location->country,
                'parent_id' => $location->parent_id,
                'status' => $location->status,
            ],
        ];
    }

    private function findLocation(int $id): Location
    {
        $location = Location::findOne($id);

        if ($location === null) {
            throw new NotFoundHttpException(
                'Location not found.'
            );
        }

        return $location;
    }
}