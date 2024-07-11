<?php

namespace App\Twig\Runtime;

use App\Enum\MultiAction;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

readonly class MultiActionExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private CollectionExtensionRuntime $collectionExtensionRuntime,
        private CategoryExtensionRuntime $categoryExtensionRuntime,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<int, array{action: string, label: string}>
     */
    public function getContextActions(): array
    {
        $contextActions = [];

        // Add To Collection
        if (!$this->isBin() && $this->collectionExtensionRuntime->workspaceHasCollections()) {
            $contextActions[] = $this->getAction(MultiAction::COLLECTION_ADD);
        }

        // Add To Collection
        if (!$this->isBin() && $this->collectionExtensionRuntime->isCollection()) {
            $contextActions[] = $this->getAction(MultiAction::COLLECTION_REMOVE);
        }

        // Add To Folder
        if (!$this->isBin() && $this->categoryExtensionRuntime->workspaceHasCategories()) {
            $contextActions[] = $this->getAction(MultiAction::CATEGORY_ADD);
        }

        // Delete
        $contextActions[] = $this->getAction(MultiAction::DELETE);

        if ($this->isBin()) {
            $contextActions[] = $this->getAction(MultiAction::UNDELETE);
        }

        return $contextActions;
    }

    public function isBin(): bool
    {
        return 'workspace_bin' === $this->requestStack->getMainRequest()?->attributes->get('_route');
    }

    /**
     * @return array{action: string, label: string}
     */
    private function getAction(MultiAction $multiAction): array
    {
        return [
            'action' => $multiAction->value,
            'label' => $this->translator->trans('multiaction.'.$multiAction->value),
        ];
    }
}
