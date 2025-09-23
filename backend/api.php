<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Webling\GitLab\GraphQLService;

require 'vendor/autoload.php';
require './_config.php';

header("Access-Control-Allow-Origin: *");

$client = new Gitlab\Client();
$client->setUrl(GITLAB_URL);
$client->authenticate(GITLAB_TOKEN, Gitlab\Client::AUTH_HTTP_TOKEN);

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
	//$users = $instance->all(['without_project_bots' => true]);

	// apply user filter
	if (isset($GLOBALS['_FILTERED_USERS']) && is_array($GLOBALS['_FILTERED_USERS'])) {
		$filtered = [];
		foreach ($users as $user) {
			if (!$user['bot'] && !in_array($user['username'], $GLOBALS['_FILTERED_USERS'])) {
				$filtered[] = $user;
			}
		}
		$users = $filtered;
	}
	return $response->withJson($users);
});

// deprecated, use /graphql/issues instead
//$app->get('/issues', function (Request $request, Response $response, array $args) use ($client) {
//	$instance = new \Gitlab\Api\Issues($client);
//	$issues = loadIssues($instance, 'None');
//
//	if (isset($request->getQueryParams()['milestone'])) {
//		$issues = array_merge($issues, loadIssues($instance, $request->getQueryParams()['milestone']));
//	}
//
//	return $response->withJson($issues);
//});
//
//function loadIssues($instance, $milestone = '') {
//	$issues = [];
//
//	// load more than 100 issues
//	$page = 1;
//	while ($page < 100) {
//		$paged_issues = $instance->all(GITLAB_PROJECT_ID, [
//			'state' => 'opened',
//			'per_page' => 100,
//			'page' => $page,
//			'milestone' => $milestone,
//		]);
//		if (count($paged_issues) > 0) {
//			foreach ($paged_issues as $issue) {
//				$issues[] = $issue;
//			}
//		} else {
//			break;
//		}
//		$page++;
//	}
//
//	// apply label filter
//	if (isset($GLOBALS['_FILTERED_LABELS']) && is_array($GLOBALS['_FILTERED_LABELS'])) {
//		$filtered = [];
//		foreach ($issues as $issue) {
//			$show = true;
//			if (isset($issue['labels']) && count($issue['labels'])) {
//				foreach ($issue['labels'] as $label) {
//					if (in_array($label, $GLOBALS['_FILTERED_LABELS'])) {
//						$show = false;
//					}
//				}
//			}
//			if ($show) {
//				$filtered[] = $issue;
//			}
//		}
//		$issues = $filtered;
//	}
//	return $issues;
//}

$app->get('/issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$result = $issues->show(GITLAB_PROJECT_ID, $args['id']);
	return $response->withJson($result);
});
$app->get('/milestones', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Milestones($client);
	$milestones = $instance->all(GITLAB_PROJECT_ID, ['state' => 'active']);
	usort($milestones, function ($a, $b) {
		return strcmp($b['title'], $a['title']);
	});
	return $response->withJson($milestones);
});
$app->get('/labels', function (Request $request, Response $response, array $args) use ($client) {
	$instance = new \Gitlab\Api\Projects($client);
	$labels = $instance->labels(GITLAB_PROJECT_ID, ['per_page' => 100]);
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
	$data = [];
	if (isset($body['user'])) {
		$data['assignee_ids'] = [$body['user']];
	}
	if (isset($body['milestone'])) {
		$data['milestone_id'] = $body['milestone'];
	}
	if (isset($body['labels'])) {
		$data['labels'] = $body['labels'];
	}
	$issues->update(GITLAB_PROJECT_ID, $args['id'], $data);
	$newIssueData = loadIssueByIid($args['id']);
	return $response->withJson($newIssueData);
});
$app->post('/close_issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$issues->update(GITLAB_PROJECT_ID, $args['id'], ['state_event' => 'close', 'assignee_ids' => [0], 'milestone_id' => 0]);
	$newIssueData = loadIssueByIid($args['id']);
	return $response->withJson($newIssueData);
});
$app->post('/reopen_issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$issues = new \Gitlab\Api\Issues($client);
	$issues->update(GITLAB_PROJECT_ID, $args['id'], ['state_event' => 'reopen']);
	$newIssueData = loadIssueByIid($args['id']);
	return $response->withJson($newIssueData);
});


// --- GraphQL Section ---

$app->get('/graphql/issues', function (Request $request, Response $response, array $args) use ($client) {
	$milestone = null;
	if (isset($request->getQueryParams()['milestone'])) {
		$milestone = trim($request->getQueryParams()['milestone']);
	}
	$endpoint = GITLAB_URL . '/api/graphql';
	$service = new GraphQLService($endpoint, GITLAB_TOKEN, GITLAB_PROJECT_FULL_PATH);
	$allIssues = $service->fetchOpenIssues();

	// filter by milestone if set
	if ($milestone !== null && $milestone !== '') {
		// filter all issues with the given milestone title, or no milestone
		$allIssues = array_filter($allIssues, function ($issue) use ($milestone) {
			return $issue['milestone'] === null || $issue['milestone']['title'] === $milestone;
		});
		$allIssues = array_values($allIssues); // reindex array
	}

	// apply label filter
	if (isset($GLOBALS['_FILTERED_LABELS']) && is_array($GLOBALS['_FILTERED_LABELS'])) {
		$filtered = [];
		foreach ($allIssues as $issue) {
			$show = true;
			if (isset($issue['labels']) && count($issue['labels']['nodes'])) {
				foreach ($issue['labels']['nodes'] as $label) {
					if (in_array($label['title'], $GLOBALS['_FILTERED_LABELS'])) {
						$show = false;
					}
				}
			}
			if ($show) {
				$filtered[] = $issue;
			}
		}
		$allIssues = $filtered;
	}

	return $response->withJson($allIssues);
});

$app->get('/graphql/issue/{id}', function (Request $request, Response $response, array $args) use ($client) {
	$iid = $args['id'];
	$issue = loadIssueByIid($iid);
	return $response->withJson($issue);
});


function loadIssueByIid($iid) {
	$endpoint = GITLAB_URL . '/api/graphql';
	$service = new GraphQLService($endpoint, GITLAB_TOKEN, GITLAB_PROJECT_FULL_PATH);
	return $service->fetchIssueByIid($iid);
}

$app->run();
