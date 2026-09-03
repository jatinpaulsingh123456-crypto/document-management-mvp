<?php

declare(strict_types=1);

namespace api\controllers;

use common\models\AuditLog;
use Yii;
use yii\web\UnauthorizedHttpException;

use yii\rest\Controller;

class SiteController extends Controller
{
    public function actionIndex(): array
    {
        return [
            'status' => 'ok',
            'message' => 'Document Management API is running.',
        ];
    }
    /**
     * GET /api/v1/security/activity
     *
     * Recent audit activity for the authenticated user.
     */
    public function actionSecurityActivity(): array
    {
        $user = Yii::$app->user->identity;

        if ($user === null) {
            throw new UnauthorizedHttpException(
                'Authentication required.'
            );
        }

        $logs = AuditLog::find()
            ->where([
                'user_id' => (int) $user->id,
            ])
            ->orderBy([
                'created_at' => SORT_DESC,
                'id' => SORT_DESC,
            ])
            ->limit(25)
            ->asArray()
            ->all();

        return [
            'success' => true,
            'data' => $logs,
        ];
    }


}
