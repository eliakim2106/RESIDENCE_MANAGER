<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\View\View;

/**
 * Pages d'information du site : questions fréquentes, contact, propriétaires, conditions et confidentialité.
 */
class PageController extends Controller
{
    public function faq(): View
    {
        return view('site.pages.faq', [
            'questions' => [
                'Réserver' => [
                    ['Comment réserver une résidence ?', 'Choisissez vos dates sur la fiche de la résidence, sélectionnez un ou plusieurs logements puis cliquez sur « Réserver ». Vous vérifiez le récapitulatif, indiquez le voyageur principal et envoyez votre demande.'],
                    ['Dois-je créer un compte ?', 'Oui : un compte client permet de suivre vos demandes, de régler en ligne et de retrouver vos bons de réservation. Il se crée en une minute, avec votre email ou avec Google / Facebook lorsque cette option est proposée. Vos dates et logements choisis sont conservés pendant l’inscription.'],
                    ['Ma réservation est-elle confirmée immédiatement ?', 'Votre demande est transmise à l’établissement, qui la confirme. Vous êtes prévenu par email et dans votre espace. Sans réponse dans le délai indiqué, la demande expire automatiquement.'],
                    ['Puis-je réserver plusieurs logements pour un groupe ?', 'Oui : sur la fiche, choisissez le nombre de logements souhaité pour chaque type. Le total se met à jour automatiquement.'],
                ],
                'Payer' => [
                    ['Quels moyens de paiement sont acceptés ?', 'Le paiement en ligne passe par notre agrégateur de paiement sécurisé : Mobile Money (Orange, MTN, Moov, Wave selon disponibilité) et carte bancaire. L’établissement peut aussi enregistrer un paiement reçu sur place.'],
                    ['Quand dois-je payer ?', 'Vous pouvez régler depuis votre espace dès l’envoi de votre demande, ou attendre sa confirmation. Le reste éventuel se règle selon les modalités de l’établissement.'],
                    ['Où trouver mon reçu ?', 'Chaque paiement génère un reçu téléchargeable depuis le détail de votre réservation, dans votre espace client.'],
                ],
                'Annuler et modifier' => [
                    ['Comment annuler une réservation ?', 'Depuis votre espace, ouvrez la réservation puis « Annuler ma réservation ». Les conditions d’annulation de l’établissement s’appliquent : elles sont rappelées sur la fiche et sur le récapitulatif.'],
                    ['Serai-je remboursé ?', 'Si vous annulez avant la date limite d’annulation gratuite, le paiement effectué est remboursé. Après cette date, ou avec une politique non remboursable, il reste acquis à l’établissement.'],
                    ['Puis-je modifier mes dates ?', 'Contactez directement l’établissement (ses coordonnées figurent dans votre réservation) : il pourra adapter votre séjour selon ses disponibilités.'],
                ],
                'Mon compte' => [
                    ['J’ai oublié mon mot de passe', 'Sur la page de connexion, cliquez sur « Mot de passe oublié ? » : vous recevrez un lien par email pour en choisir un nouveau.'],
                    ['Comment laisser un avis ?', 'Après votre départ, un formulaire d’avis apparaît dans le détail de la réservation. Seuls les voyageurs ayant séjourné peuvent noter une résidence.'],
                ],
                'Propriétaires' => [
                    ['Comment proposer mon établissement ?', 'Créez un compte propriétaire, ajoutez votre établissement et ses logements puis soumettez-le : notre équipe le vérifie avant sa mise en ligne.'],
                    ['Comment suis-je payé ?', 'Les paiements en ligne de vos clients vous sont reversés par DS HOLDING ; le détail figure dans « Mes reversements » de votre espace.'],
                ],
            ],
        ]);
    }

    public function contact(): View
    {
        return view('site.pages.contact');
    }

    public function owners(): View
    {
        return view('site.pages.proprietaires', [
            'plans' => SubscriptionPlan::query()->active()->ordered()->get(),
        ]);
    }

    public function terms(): View
    {
        return view('site.pages.conditions');
    }

    public function privacy(): View
    {
        return view('site.pages.confidentialite');
    }
}
