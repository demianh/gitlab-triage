<?php
declare(strict_types=1);

namespace Webling\GitLab;

final class GitLabGraphQLClient
{
	private string $endpoint;
	private string $token;

	public function __construct(string $endpoint, string $token)
	{
		$this->endpoint = $endpoint;
		$this->token    = $token;
	}

	/**
	 * Führt eine GraphQL-Query aus und gibt das "data"-Array zurück.
	 * Wirft RuntimeException bei HTTP- oder GraphQL-Fehlern.
	 */
	public function query(string $query, array $variables = []): array
	{
		$payload = json_encode(
			['query' => $query, 'variables' => $variables],
			JSON_UNESCAPED_SLASHES
		);

		$ch = curl_init($this->endpoint);
		curl_setopt_array($ch, [
			CURLOPT_POST           => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $this->token,
			],
			CURLOPT_POSTFIELDS     => $payload,
		]);

		$resp = curl_exec($ch);
		if ($resp === false) {
			throw new \RuntimeException('cURL error: ' . curl_error($ch));
		}
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($status < 200 || $status >= 300) {
			throw new \RuntimeException("HTTP $status: $resp");
		}

		$decoded = json_decode($resp, true);
		if (!is_array($decoded)) {
			throw new \RuntimeException('Invalid JSON response: ' . substr($resp, 0, 500));
		}

		if (isset($decoded['errors'])) {
			$messages = array_map(
				fn($e) => $e['message'] ?? json_encode($e),
				(array)$decoded['errors']
			);
			throw new \RuntimeException('GraphQL errors: ' . implode(' | ', $messages));
		}

		return $decoded['data'] ?? [];
	}

	/**
	 * Hilfsfunktion für Pagination.
	 * $fetchPage wird mit dem Cursor ($after) aufgerufen und muss ein Array
	 * [nodes, endCursor, hasNextPage] zurückgeben.
	 * Gibt alle Nodes als flaches Array zurück.
	 */
	public function paginate(callable $fetchPage): array
	{
		$all = [];
		$after = null;

		do {
			[$nodes, $endCursor, $hasNext] = $fetchPage($after);
			if (!empty($nodes)) {
				// numerische Indizes erzwingen
				$all = array_merge($all, array_values($nodes));
			}
			$after = $endCursor;
		} while ($hasNext === true);

		return $all;
	}
}
