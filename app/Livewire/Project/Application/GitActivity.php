<?php

namespace App\Livewire\Project\Application;

use App\Models\Application;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitActivity extends Component
{
    public Application $application;
    public $commits = [];
    public $pullRequests = [];
    public $rate_limit_remaining;
    public $error_message = null;
    public $debug_info = [];

    public function mount()
    {
        $this->loadGitActivity();
    }

    public function loadGitActivity()
    {
        try {
            if ($this->application->is_github_based()) {
                $this->loadGithubActivity();
            } elseif ($this->application->is_gitlab_based()) {
                $this->loadGitlabActivity();
            }
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
            Log::error('Git Activity Error', [
                'error' => $e->getMessage(),
                'application' => $this->application->id
            ]);
        }
    }

    protected function loadGithubActivity()
    {
        $repo = $this->application->git_repository;

        // Initialize debug info with all required keys
        $this->debug_info = [
            'repo' => $repo,
            'branch' => $this->application->git_branch,
            'auth_type' => $this->application->git_type ?? 'unknown',
            'using_github_app' => true,
            'has_token' => false,  // Default value
            'commits_status' => null,
            'prs_status' => null,
            'commits_body' => [],
            'prs_body' => []
        ];

        try {
            // Get commits
            $commitsResponse = githubApi(
                source: $this->application->source,
                endpoint: "/repos/{$repo}/commits?per_page=10&sha={$this->application->git_branch}"
            );

            $this->commits = $commitsResponse['data'];
            $this->rate_limit_remaining = $commitsResponse['rate_limit_remaining'];
            $this->debug_info['commits_status'] = 200;
            $this->debug_info['commits_body'] = $commitsResponse['data'];
            $this->debug_info['has_token'] = true; // Set to true if API call succeeds

            // Get PRs
            $prsResponse = githubApi(
                source: $this->application->source,
                endpoint: "/repos/{$repo}/pulls?state=all&per_page=5"
            );

            $this->pullRequests = $prsResponse['data'];
            $this->debug_info['prs_status'] = 200;
            $this->debug_info['prs_body'] = $prsResponse['data'];
        } catch (\Exception $e) {
            $this->error_message = "GitHub API Error: " . $e->getMessage();
            Log::error('GitHub API Error', [
                'error' => $e->getMessage(),
                'application' => $this->application->id
            ]);
        }
    }

    protected function getGithubToken()
    {
        try {
            // Check if we can access the API
            $response = githubApi(
                source: $this->application->source,
                endpoint: "/repos/{$this->application->git_repository}/commits",
                method: 'HEAD'
            );

            $this->rate_limit_remaining = $response['rate_limit_remaining'];

            // Return true to indicate we can use githubApi helper
            return true;
        } catch (\Exception $e) {
            Log::error('GitHub App Authentication Error', [
                'error' => $e->getMessage(),
                'application' => $this->application->id
            ]);
            return false;
        }
    }

    public function render()
    {
        return view('livewire.project.application.git-activity');
    }
}
