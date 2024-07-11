<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\VoterHelperExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VoterHelperExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can_upload_asset', [VoterHelperExtensionRuntime::class, 'canUploadAsset']),
            new TwigFunction('can_edit_asset', [VoterHelperExtensionRuntime::class, 'canEditAsset']),
            new TwigFunction('can_create_collection', [VoterHelperExtensionRuntime::class, 'canCreateCollection']),
            new TwigFunction('can_send_invite', [VoterHelperExtensionRuntime::class, 'canSendInvite']),
            new TwigFunction('can_edit_admins', [VoterHelperExtensionRuntime::class, 'canEditAdmins']),
            new TwigFunction('can_edit_users', [VoterHelperExtensionRuntime::class, 'canEditUsers']),
            new TwigFunction('can_view_log', [VoterHelperExtensionRuntime::class, 'canViewLog']),
            new TwigFunction('can_create_categories', [VoterHelperExtensionRuntime::class, 'canCreateCategories']),
            new TwigFunction('can_delete_category', [VoterHelperExtensionRuntime::class, 'canDeleteCategory']),
            new TwigFunction('can_edit_category', [VoterHelperExtensionRuntime::class, 'canEditCategory']),
            new TwigFunction('can_edit_membership', [VoterHelperExtensionRuntime::class, 'canEditMembership']),
            new TwigFunction('can_delete_membership', [VoterHelperExtensionRuntime::class, 'canDeleteMembership']),
        ];
    }
}
