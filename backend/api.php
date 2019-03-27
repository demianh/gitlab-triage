<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';
require './_config.php';

header("Access-Control-Allow-Origin: *");

$client = \Gitlab\Client::create(GITLAB_URL)->authenticate(GITLAB_TOKEN, \Gitlab\Client::AUTH_URL_TOKEN);

$configuration = [
	'settings' => [
		'displayErrorDetails' => true,
	],
];
$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);

// Allow CORS Requests
$app->add(function ($req, $res, $next) {
	$response = $next($req, $res);
	return $response
			->withHeader('Access-Control-Allow-Origin', '*')
			->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
			->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/users', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Users($client);
	$users = $instance->all();
	return $response->withJson($users);
});
$app->get('/issues', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Issues($client);
	$issues = [];

	// load more than 100 issues
	$page = 1;
	while ($page < 100) {
		$paged_issues = $instance->all(GITLAB_PROJECT_ID, [
			'state' => 'opened',
			'per_page' => 100,
			'page' => $page,
			'milestone' => 'None',
		]);
		if (count($paged_issues) > 0) {
			foreach ($paged_issues as $issue) {
				$issues[] = $issue;
			}
		} else {
			break;
		}
		$page++;
	}
	return $response->withJson($issues);
});
$app->get('/issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$result = $issues->show(GITLAB_PROJECT_ID, $args['id']);
	return $response->withJson($result);
});
$app->get('/milestones', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Milestones($client);
	$milestones = $instance->all(GITLAB_PROJECT_ID, ['state' => 'active']);
	return $response->withJson($milestones);
});
$app->get('/labels', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Projects($client);
	$labels = $instance->labels(GITLAB_PROJECT_ID);
	$labels_indexed = [];
	foreach ($labels as $label) {
		$labels_indexed[$label['name']] = $label;
	}
	return $response->withJson($labels_indexed);
});
$app->get('/project', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Projects($client);
	$project = $instance->show(GITLAB_PROJECT_ID);
	return $response->withJson($project);
});
$app->post('/assign_issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$body = $request->getParsedBody();
	$issues = new \Gitlab\Api\Issues($client);
	$result = $issues->update(GITLAB_PROJECT_ID, $args['id'], ['assignee_ids' => [$body['user']], 'milestone_id' => $body['milestone']]);
	return $response->withJson($result);
});
$app->post('/close_issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$result = $issues->update(GITLAB_PROJECT_ID, $args['id'], ['state_event' => 'close', 'assignee_ids' => [0], 'milestone_id' => 0]);
	return $response->withJson($result);
});
$app->post('/reopen_issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$result = $issues->update(GITLAB_PROJECT_ID, $args['id'], ['state_event' => 'reopen']);
	return $response->withJson($result);
});

$app->run();
