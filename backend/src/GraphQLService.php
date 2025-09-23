<?php
declare(strict_types=1);

namespace Webling\GitLab;

use RuntimeException;

final class GraphQLService
{
    private GitLabGraphQLClient $client;
    private string $fullPath;

    public function __construct(string $endpoint, string $token, string $fullPath)
    {
        $this->client   = new GitLabGraphQLClient($endpoint, $token);
        $this->fullPath = $fullPath;
    }

    /**
     * Lädt alle offenen Issues; optional per Milestone-Titel filtern.
     *
     * @param int $first
     * @param string|string[]|null $milestoneTitles  Einzelner Titel, Liste von Titeln oder null
     */
    public function fetchOpenIssues(int $first = 100, string|array|null $milestoneTitles = null): array
    {
        // Zu [string] normalisieren oder null lassen
        if (is_string($milestoneTitles)) {
            $milestoneTitles = trim($milestoneTitles);
            $milestoneTitles = ($milestoneTitles === '') ? null : [$milestoneTitles];
        } elseif (is_array($milestoneTitles)) {
            $milestoneTitles = array_values(array_filter(array_map('trim', $milestoneTitles), fn($v) => $v !== ''));
            if ($milestoneTitles === []) {
                $milestoneTitles = null;
            }
        }

        $query = <<<'GQL'
        query($fullPath: ID!, $first: Int!, $after: String, $milestoneTitles: [String!]) {
          project(fullPath: $fullPath) {
            issues(
              first: $first,
              after: $after,
              state: opened,
              milestoneTitle: $milestoneTitles
            ) {
              nodes {
                id
                iid
                title
                description
                state
                createdAt
                webUrl
                weight
                status { id name color }
                milestone { id iid title }
                labels(first: 100) { nodes { id title color } }
                assignees(first: 100) { nodes { id avatarUrl name username } }
                notes(filter: ONLY_COMMENTS) { count }
                author { id avatarUrl name username }
              }
              pageInfo { hasNextPage endCursor }
            }
          }
        }
        GQL;

        $fetchPage = function (?string $after) use ($query, $first, $milestoneTitles) {
            $data = $this->client->query($query, [
                'fullPath'         => $this->fullPath,
                'first'            => $first,
                'after'            => $after,
                // Wichtig: null übergeben, wenn kein Filter – nicht [].
                'milestoneTitles'  => $milestoneTitles,
            ]);

            $project = $data['project'] ?? null;
            if (!$project) {
                throw new RuntimeException('Projekt nicht gefunden oder Zugriff verweigert.');
            }

            $issues   = $project['issues']['nodes']    ?? [];
            $pageInfo = $project['issues']['pageInfo'] ?? ['hasNextPage' => false, 'endCursor' => null];

            return [$issues, $pageInfo['endCursor'] ?? null, (bool)($pageInfo['hasNextPage'] ?? false)];
        };

        return $this->client->paginate($fetchPage);
    }

     /**
     * Load a single issue by IID (project-scoped issue number).
     *
     * @param string|int $iid  The issue IID (e.g. 42, not the global gid).
     * @return array|null
     */
    public function fetchIssueByIid(string|int $iid): ?array
    {
        $query = <<<'GQL'
        query($fullPath: ID!, $iid: String!) {
          project(fullPath: $fullPath) {
            issue(iid: $iid) {
              id
              iid
              title
              description
              state
              createdAt
              webUrl
              weight
              status { id name color }
              milestone { id iid title }
              labels(first: 100) { nodes { id title color } }
              assignees(first: 100) { nodes { id avatarUrl name username } }
              notes(filter: ONLY_COMMENTS) { count }
              author { id avatarUrl name username }
            }
          }
        }
        GQL;

        $data = $this->client->query($query, [
            'fullPath' => $this->fullPath,
            'iid'      => (string)$iid,
        ]);

        $project = $data['project'] ?? null;
        if (!$project || !isset($project['issue'])) {
            return null; // not found or no access
        }

        return $project['issue'];
    }

}
