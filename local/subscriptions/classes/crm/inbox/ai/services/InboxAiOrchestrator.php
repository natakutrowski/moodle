<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\cache\InboxAiCacheKeyBuilder;
use local_subscriptions\crm\inbox\ai\cache\InboxAiCachePolicy;
use local_subscriptions\crm\inbox\ai\domain\InboxAiCapability;
use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;
use local_subscriptions\crm\inbox\ai\prompts\InboxAiPromptVersionRegistry;
use local_subscriptions\crm\inbox\ai\providers\InboxAiProviderRegistry;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiResultRepository;
use local_subscriptions\crm\inbox\ai\safety\InboxAiContentSanitizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiDataMinimizer;
use local_subscriptions\crm\inbox\ai\safety\InboxAiSafetyPolicy;
use local_subscriptions\crm\inbox\ai\safety\InboxAiErrorSanitizer;
use local_subscriptions\crm\inbox\ai\validation\InboxAiResultValidator;

final class InboxAiOrchestrator {

    public function __construct(
        private readonly InboxAiProviderRegistry $providers,
        private readonly InboxAiResultRepository $results,
        private readonly InboxAiSafetyPolicy $safety,
        private readonly InboxAiContentSanitizer $sanitizer,
        private readonly InboxAiPromptVersionRegistry $promptversions,
        private readonly InboxAiCacheKeyBuilder $cachekeys,
        private readonly InboxAiCachePolicy $cachepolicy,
        private readonly InboxAiResultValidator $validator,
        private readonly ?InboxAiDataMinimizer $minimizer = null,
        private readonly ?InboxAiErrorSanitizer $errors = null
    ) {
    }

    public function analyse(
        InboxAiRequest $request,
        bool $force = false
    ): InboxAiResult {
        if (
            !InboxAiCapability::is_valid(
                $request->capability
            )
        ) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox AI capability.'
            );
        }

        $decision = $this->safety->evaluate(
            $request->capability,
            $request->context
        );

        if (!$decision->allowed) {
            return InboxAiResult::blocked(
                $request->capability,
                $decision->reason
                    ?? 'The request was blocked.',
                $decision->warnings
            );
        }

        $content = $this->sanitizer->sanitize(
            $request->content
        );

        if ($content === '') {
            return InboxAiResult::blocked(
                $request->capability,
                'No usable content was provided.',
                $decision->warnings
            );
        }

        $normalizedrequest =
            new InboxAiRequest(
                $request->capability,
                $request->threadid,
                $request->messageid,
                $content,
                $request->requestedlanguage,
                $request->context,
                $request->constraints,
                $request->actorid
            );

        /*
         * La minimisation doit intervenir avant :
         *
         * - la sélection et l’appel du provider ;
         * - le calcul du hash ;
         * - le calcul de la clé de cache.
         *
         * Ainsi, le cache représente exactement les données
         * réellement transmises au fournisseur.
         */
        if ($this->minimizer !== null) {
            $normalizedrequest =
                $this->minimizer->minimize(
                    $normalizedrequest
                );
        }

        $preferredprovider =
            $this->preferred_provider(
                $normalizedrequest
            );

        $provider = $this->providers->resolve(
            $normalizedrequest->capability,
            $preferredprovider
        );

        if (!$provider) {
            return InboxAiResult::unavailable(
                $normalizedrequest->capability,
                $preferredprovider ?? 'none',
                'No available provider supports this capability.'
            );
        }

        $promptversion =
            $this->promptversions->get(
                $normalizedrequest->capability
            );

        $inputhash =
            $normalizedrequest->input_hash(
                $promptversion
            );

        $cachekey = $this->cachekeys->build(
            $normalizedrequest,
            $provider->key(),
            $promptversion
        );

        if (!$force) {
            $cached =
                $this->results->find_fresh(
                    $cachekey
                );

            if ($cached) {
                return new InboxAiResult(
                    $cached->status,
                    $cached->capability,
                    $cached->provider,
                    $cached->model,
                    $cached->data,
                    $cached->confidence,
                    $cached->warnings,
                    $cached->error,
                    $cached->generatedat,
                    array_merge(
                        $cached->metadata,
                        ['cachehit' => true]
                    )
                );
            }
        }

        $startedat = microtime(true);

        try {
            $providerresult =
                $provider->analyse(
                    $normalizedrequest
                );

            $latencyms = (int)round(
                (
                    microtime(true) -
                    $startedat
                ) * 1000
            );

            /*
             * On enrichit d’abord le résultat avec les
             * avertissements et métadonnées communes.
             */
            $result = new InboxAiResult(
                $providerresult->status,
                $providerresult->capability,
                $providerresult->provider,
                $providerresult->model,
                $providerresult->data,
                $providerresult->confidence,
                array_values(
                    array_unique(
                        array_merge(
                            $decision->warnings,
                            $providerresult->warnings
                        )
                    )
                ),
                $providerresult->error,
                $providerresult->generatedat
                    ?? time(),
                array_merge(
                    $providerresult->metadata,
                    [
                        'cachehit' => false,
                        'latencyms' => $latencyms,
                        'promptversion' =>
                            $promptversion,
                    ]
                )
            );

            /*
             * Validation locale obligatoire.
             *
             * Même avec Structured Outputs, on ne fait
             * jamais confiance aveuglément au provider.
             */
            $validationerrors =
                $this->validator->validate(
                    $normalizedrequest,
                    $result
                );

            if ($validationerrors) {
                $result = InboxAiResult::failed(
                    $normalizedrequest->capability,
                    $provider->key(),
                    implode(
                        ' ',
                        $validationerrors
                    ),
                    array_merge(
                        $result->metadata,
                        [
                            'validationfailed' =>
                                true,
                            'validationerrors' =>
                                $validationerrors,
                            'model' =>
                                $result->model,
                        ]
                    )
                );
            }
        } catch (\Throwable $exception) {
            $latencyms = (int)round(
                (
                    microtime(true) -
                    $startedat
                ) * 1000
            );

            $errors =
                $this->errors ??
                new InboxAiErrorSanitizer();

            debugging(
                'CRM Inbox AI provider failure: ' .
                get_class($exception) .
                ' - ' .
                $exception->getMessage(),
                DEBUG_DEVELOPER
            );

            $result = InboxAiResult::failed(
                $normalizedrequest->capability,
                $provider->key(),
                $errors->public_message(
                    $exception
                ),
                array_merge(
                    [
                        'cachehit' =>
                            false,

                        'latencyms' =>
                            $latencyms,

                        'promptversion' =>
                            $promptversion,
                    ],
                    $errors->diagnostic_metadata(
                        $exception
                    )
                )
            );
        }

        $generatedat =
            $result->generatedat ?? time();

        $expiresat =
            $this->cachepolicy->expires_at(
                $normalizedrequest->capability,
                $result->status,
                $generatedat
            );

        /*
         * Même un résultat refusé par la validation est
         * enregistré comme failed pour permettre :
         *
         * - le diagnostic ;
         * - l’audit ;
         * - le suivi des erreurs fournisseur ;
         * - la prévention des boucles silencieuses.
         */
        $this->results->save(
            $normalizedrequest,
            $result,
            $promptversion,
            $inputhash,
            $cachekey,
            $expiresat
        );

        return $result;
    }

    private function preferred_provider(
        InboxAiRequest $request
    ): ?string {
        $provider = trim(
            (string)(
                $request->constraints['provider']
                ?? ''
            )
        );

        return $provider !== ''
            ? $provider
            : null;
    }
}