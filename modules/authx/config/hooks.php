<?php
$config['authx-card.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';

$config['authx-card.api.v1.user.GET'][] = 'AuthX_Card_Lims::api_user_get';
$config['authx-card.api.v1.cards.GET'][] = 'AuthX_Card_Lims::api_cards_get';


$config['authx-face.api.v1.middlewares.*'][] = 'API_Middlewares::gapperTokenAuth';

$config['authx-face.api.v1.user.GET'][] = 'AuthX_Face_Lims::api_user_get';
$config['authx-face.api.v1.features.GET'][] = 'AuthX_Face_Lims::api_features_get';