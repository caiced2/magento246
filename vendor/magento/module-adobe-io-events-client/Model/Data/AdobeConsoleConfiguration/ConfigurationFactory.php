<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeIoEventsClient\Model\Data\AdobeConsoleConfiguration;

/**
 * Factory for Adobe Console configuration object
 */
class ConfigurationFactory
{
    /**
     * @var AdobeConsoleConfigurationFactory
     */
    private AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory;

    /**
     * @var CredentialsFactory
     */
    private CredentialsFactory $credentialsFactory;

    /**
     * @var JWTFactory
     */
    private JWTFactory $jwtFactory;

    /**
     * @var OAuthFactory
     */
    private OAuthFactory $oauthFactory;

    /**
     * @var OrganizationFactory
     */
    private OrganizationFactory $organizationFactory;

    /**
     * @var ProjectFactory
     */
    private ProjectFactory $projectFactory;

    /**
     * @var WorkspaceFactory
     */
    private WorkspaceFactory $workspaceFactory;

    /**
     * @var WorkspaceDetailsFactory
     */
    private WorkspaceDetailsFactory $workspaceDetailsFactory;

    /**
     * @param AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory
     * @param CredentialsFactory $credentialsFactory
     * @param JWTFactory $jwtFactory
     * @param OAuthFactory $oauthFactory
     * @param OrganizationFactory $organizationFactory
     * @param ProjectFactory $projectFactory
     * @param WorkspaceFactory $workspaceFactory
     * @param WorkspaceDetailsFactory $workspaceDetailsFactory
     */
    public function __construct(
        AdobeConsoleConfigurationFactory $adobeConsoleConfigurationFactory,
        CredentialsFactory $credentialsFactory,
        JWTFactory $jwtFactory,
        OAuthFactory $oauthFactory,
        OrganizationFactory $organizationFactory,
        ProjectFactory $projectFactory,
        WorkspaceFactory $workspaceFactory,
        WorkspaceDetailsFactory $workspaceDetailsFactory
    ) {
        $this->adobeConsoleConfigurationFactory = $adobeConsoleConfigurationFactory;
        $this->credentialsFactory = $credentialsFactory;
        $this->jwtFactory = $jwtFactory;
        $this->oauthFactory = $oauthFactory;
        $this->organizationFactory = $organizationFactory;
        $this->projectFactory = $projectFactory;
        $this->workspaceFactory = $workspaceFactory;
        $this->workspaceDetailsFactory = $workspaceDetailsFactory;
    }

    /**
     * Create Adobe Console Configuration from API Response Data
     *
     * @param array $data
     * @return AdobeConsoleConfiguration
     */
    public function create(array $data): AdobeConsoleConfiguration
    {
        $configuration = $this->adobeConsoleConfigurationFactory->create();

        $projectData = $data["project"];
        $project = $this->projectFactory->create();
        $configuration->setProject($project);
        $project->setId($projectData["id"]);
        $project->setName($projectData["name"]);
        $project->setTitle($projectData["title"]);

        $orgData = $projectData["org"];
        $org = $this->organizationFactory->create();
        $project->setOrganization($org);

        $org->setName($orgData["name"]);
        $org->setId($orgData["id"]);
        $org->setImsOrgId($orgData["ims_org_id"]);

        $workspaceData = $projectData["workspace"];
        $workspace = $this->workspaceFactory->create();
        $workspace->setId($workspaceData["id"]);
        $workspace->setName($workspaceData["name"]);
        $workspace->setTitle($workspaceData["title"]);
        $workspace->setActionUrl($workspaceData["action_url"]);
        $workspace->setAppUrl($workspaceData["app_url"]);
        $project->setWorkspace($workspace);

        $detailsData = $workspaceData["details"];
        $details = $this->workspaceDetailsFactory->create();
        $workspace->setDetails($details);

        $credentialsArray = [];
        foreach ($detailsData["credentials"] as $credentialData) {
            if (!isset($credentialData["jwt"]) && !isset($credentialData['oauth_server_to_server'])) {
                continue;
            }
            /** @var Credentials $credentials */
            $credentials = $this->credentialsFactory->create();
            $credentials->setId($credentialData["id"]);
            $credentials->setName($credentialData["name"]);
            $credentials->setIntegrationType($credentialData["integration_type"]);

            if (isset($credentialData["jwt"])) {
                $jwt = $this->createJWT($credentialData["jwt"]);
                $credentials->setJwt($jwt);
            } elseif (isset($credentialData["oauth_server_to_server"])) {
                $oauth = $this->createOauth($credentialData["oauth_server_to_server"]);
                $credentials->setOAuth($oauth);
            }

            $credentialsArray[] = $credentials;
        }
        $details->setCredentials($credentialsArray);

        return $configuration;
    }

    /**
     * Creates JWT credentials from given data.
     *
     * @param array $data
     * @return JWT
     */
    private function createJWT(array $data): JWT
    {
        /** @var JWT $jwt */
        $jwt = $this->jwtFactory->create();
        $jwt->setClientId($data["client_id"]);
        $jwt->setClientSecret($data["client_secret"]);
        $jwt->setTechnicalAccountEmail($data["technical_account_email"]);
        $jwt->setTechnicalAccountId($data["technical_account_id"]);
        $jwt->setMetaScopes($data["meta_scopes"]);

        return $jwt;
    }

    /**
     * Creates OAuth credentials from given data.
     *
     * @param array $data
     * @return OAuth
     */
    private function createOauth(array $data): OAuth
    {
        /** @var OAuth $oauth */
        $oauth = $this->oauthFactory->create();
        $oauth->setClientId($data["client_id"]);
        $oauth->setClientSecrets($data["client_secrets"]);
        $oauth->setTechnicalAccountEmail($data["technical_account_email"]);
        $oauth->setTechnicalAccountId($data["technical_account_id"]);
        $oauth->setScopes($data["scopes"]);

        return $oauth;
    }
}
