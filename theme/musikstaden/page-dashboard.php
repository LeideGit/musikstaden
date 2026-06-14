<?php
/**
 * Dashboard page template.
 *
 * Template Name: Dashboard
 *
 * @package Musikstaden
 */

get_header();

$user_id = get_current_user_id();
$bands   = musikstaden_get_user_bands( $user_id );
$invites = musikstaden_get_user_pending_invites( $user_id );
$notice  = sanitize_text_field( wp_unslash( $_GET['invite'] ?? '' ) );
$studio  = sanitize_key( wp_unslash( $_GET['studio'] ?? '' ) );
?>

<section class="dashboard section">
	<div class="container">
		<h1><?php ms_e( 'dashboard.title', 'My Dashboard' ); ?></h1>

		<?php if ( $studio ) : ?>
			<?php
			$studio_messages = array(
				'saved'       => ms__( 'studio.notice_saved', 'Ändringar sparade.' ),
				'published'   => ms__( 'studio.notice_published', 'Bandet är publicerat!' ),
				'unpublished' => ms__( 'studio.notice_unpublished', 'Bandet är avpublicerat.' ),
				'deleted'     => ms__( 'studio.notice_deleted', 'Bandet har tagits bort.' ),
				'limit'       => ms__( 'dashboard.limit', 'Bandgräns nådd (max 5).' ),
				'error'       => ms__( 'studio.error_generic', 'Något gick fel.' ),
			);
			if ( isset( $studio_messages[ $studio ] ) ) :
				?>
			<div class="notice notice-<?php echo in_array( $studio, array( 'saved', 'published', 'unpublished', 'deleted' ), true ) ? 'success' : 'error'; ?>">
				<?php echo esc_html( $studio_messages[ $studio ] ); ?>
			</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo in_array( $notice, array( 'sent', 'accepted' ), true ) ? 'success' : 'error'; ?>">
				<?php
				$messages = array(
					'sent'          => musikstaden_get_lang() === 'sv' ? 'Inbjudan skickad.' : 'Invite sent.',
					'accepted'      => musikstaden_get_lang() === 'sv' ? 'Inbjudan accepterad!' : 'Invite accepted!',
					'error'         => musikstaden_get_lang() === 'sv' ? 'Kunde inte skicka inbjudan.' : 'Could not send invite.',
					'exists'        => musikstaden_get_lang() === 'sv' ? 'Personen är redan medlem.' : 'Person is already a member.',
					'limit'         => musikstaden_get_lang() === 'sv' ? 'Bandgräns nådd (max 5).' : 'Band limit reached (max 5).',
					'invalid'       => musikstaden_get_lang() === 'sv' ? 'Ogiltig inbjudan.' : 'Invalid invite.',
					'expired'       => musikstaden_get_lang() === 'sv' ? 'Inbjudan har gått ut.' : 'Invite expired.',
					'wrong_account' => musikstaden_get_lang() === 'sv' ? 'Logga in med rätt konto för att acceptera.' : 'Log in with the correct account to accept.',
				);
				echo esc_html( $messages[ $notice ] ?? $notice );
				?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $invites ) ) : ?>
		<section class="dashboard__section">
			<h2><?php ms_e( 'dashboard.pending_invites', 'Pending Invites' ); ?></h2>
			<ul class="dashboard__invites">
				<?php foreach ( $invites as $invite ) : ?>
					<li>
						<?php echo esc_html( $invite['band_name'] ); ?> (<?php echo esc_html( ucfirst( $invite['role'] ) ); ?>)
						<a href="<?php echo esc_url( add_query_arg( 'ms_invite', $invite['token'] ) ); ?>" class="btn btn--primary btn--sm">
							<?php ms_e( 'dashboard.accept_invite', 'Accept' ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php endif; ?>

		<section class="dashboard__section">
			<div class="dashboard__header">
				<h2><?php ms_e( 'dashboard.my_bands', 'My Bands' ); ?></h2>
				<?php if ( musikstaden_user_can_create_band( $user_id ) ) : ?>
					<a href="<?php echo esc_url( musikstaden_band_create_url() ); ?>" class="btn btn--primary btn--glow">
						<?php ms_e( 'dashboard.create_band', 'Create New Band' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( empty( $bands ) ) : ?>
				<p><?php ms_e( 'dashboard.no_bands', 'You have no bands yet.' ); ?></p>
				<?php if ( musikstaden_user_can_create_band( $user_id ) ) : ?>
					<p><a href="<?php echo esc_url( musikstaden_band_create_url() ); ?>" class="btn btn--primary"><?php ms_e( 'dashboard.create_first', 'Skapa ditt första band' ); ?></a></p>
				<?php endif; ?>
			<?php else : ?>
				<div class="dashboard__bands">
					<?php foreach ( $bands as $band_id => $role ) : ?>
						<?php
						$band = get_post( (int) $band_id );
						if ( ! $band || 'band' !== $band->post_type ) {
							continue;
						}
						$can_edit   = musikstaden_user_can_edit_band( $user_id, (int) $band_id );
						$can_pub    = musikstaden_band_studio_can_publish( (int) $band_id );
						$thumb      = get_the_post_thumbnail_url( $band, 'band-card' );
						?>
						<article class="dashboard-card">
							<?php if ( $thumb ) : ?>
								<div class="dashboard-card__thumb">
									<img src="<?php echo esc_url( $thumb ); ?>" alt="">
								</div>
							<?php endif; ?>
							<h3><?php echo esc_html( $band->post_title ); ?></h3>
							<div class="dashboard-card__meta">
								<span class="tag tag--role"><?php echo esc_html( ucfirst( $role ) ); ?></span>
								<span class="tag tag--status tag--status-<?php echo esc_attr( $band->post_status ); ?>">
									<?php
									echo 'publish' === $band->post_status
										? esc_html( ms__( 'studio.status_live', 'Publicerad' ) )
										: esc_html( ms__( 'studio.status_draft', 'Utkast' ) );
									?>
								</span>
							</div>
							<?php if ( $can_edit && ! $can_pub && 'publish' !== $band->post_status ) : ?>
								<p class="dashboard-card__hint"><?php ms_e( 'dashboard.complete_profile', 'Fyll i alla obligatoriska fält för att publicera.' ); ?></p>
							<?php endif; ?>
							<div class="dashboard-card__actions">
								<?php if ( 'publish' === $band->post_status ) : ?>
									<a href="<?php echo esc_url( musikstaden_band_url( $band ) ); ?>" class="btn btn--outline btn--sm"><?php ms_e( 'dashboard.view', 'View page' ); ?></a>
								<?php endif; ?>
								<?php if ( $can_edit ) : ?>
									<a href="<?php echo esc_url( musikstaden_band_edit_url( (int) $band_id ) ); ?>" class="btn btn--primary btn--sm"><?php ms_e( 'dashboard.edit', 'Edit' ); ?></a>
								<?php endif; ?>
							</div>
							<?php if ( $can_edit ) : ?>
								<?php musikstaden_render_invite_form( (int) $band_id ); ?>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
</section>

<?php
get_footer();
