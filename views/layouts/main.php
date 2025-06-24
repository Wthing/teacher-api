<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use app\widgets\Alert;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\helpers\Url;

AppAsset::register($this);

function isActiveNav($route) {
    return Yii::$app->controller->route === $route ? 'active text-primary fw-bold' : 'text-dark';
}
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>

    <?php $this->head() ?>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f9fafb;
        }

        .sidebar {
            height: calc(100vh - 56px);
            position: fixed;
            width: 220px;
            background: #ffffff;
            border-right: 1px solid #dee2e6;
            top: 56px;
            left: 0;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .content {
            margin-left: 220px;
            padding: 80px 20px 20px; /* top-padding для фиксированного navbar */
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- Верхний навбар -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="/">🌐 Мой портал</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#topNavDropdown" aria-controls="topNavDropdown"
                aria-expanded="false" aria-label="Toggle nav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="topNavDropdown">
            <ul class="navbar-nav">
                <?php if (Yii::$app->user->isGuest): ?>
                    <li class="nav-item">
                        <?= Html::a('Войти', ['/site/login'], ['class' => 'nav-link ' . isActiveNav('site/login')]) ?>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <?= Html::beginForm(['/site/logout'], 'post') .
                        Html::submitButton(
                            '🔓 Выход (' . Html::encode(Yii::$app->user->identity->username) . ')',
                            ['class' => 'nav-link btn', 'style' => 'padding: 0; text-decoration: none;']
                        ) .
                        Html::endForm() ?>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<!-- Боковая панель -->
<div class="sidebar pt-3">
    <ul class="nav flex-column px-3">
        <li class="nav-item mb-1">
            <?= Html::a('🏠 Мой профиль', ['/site/profile', 'profileId' => Yii::$app->user->id], ['class' => 'nav-link ']) ?>
        </li>
        <li class="nav-item mb-1">
            <?= Html::a('🔍 Поиск', ['/data/search'], ['class' => 'nav-link ']) ?>
        </li>
        <?php if (Yii::$app->user->can('/confirm-application/*')): ?>
            <li class="nav-item mb-1">
                <?= Html::a('✅ Подтверждения', ['/confirm-application/index'], ['class' => 'nav-link ']) ?>
            </li>
        <?php endif; ?>
        <li class="nav-item mb-1">
            <?= Html::a('ℹ️ О сайте', ['/site/about'], ['class' => 'nav-link ']) ?>
        </li>
        <li class="nav-item mb-1">
            <?= Html::a('📨 Обратная связь', ['/site/contact'], ['class' => 'nav-link ']) ?>
        </li>
    </ul>

    <?php if (Yii::$app->user->can('/super-user/*')): ?>
        <ul class="nav flex-column mb-0">
            <li class="nav-item mt-2 border-top pt-2">
                <?= Html::a('🛠️ Админ-панель', ['/super-user/index'], ['class' => 'nav-link ']) ?>
            </li>
        </ul>
    <?php endif ?>
</div>

<!-- Основной контент -->
<main class="content">
    <?= Breadcrumbs::widget([
        'links' => $this->params['breadcrumbs'] ?? [],
    ]) ?>
    <?= Alert::widget() ?>
    <?= $content ?>
</main>

<!-- Скрипты -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
