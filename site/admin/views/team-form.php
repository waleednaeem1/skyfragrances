<?php
defined('SKYFR') || exit;
$action = $existing !== null ? '/admin/team/' . (int) $existing['id'] : '/admin/team/new';
$err = static fn (string $key): string => (string) ($errors[$key] ?? '');
$moduleOptions = [];
foreach (TEAM_MODULES as $key => [$label, $description]) {
    $moduleOptions[$key] = $label . ' — ' . $description;
}
?>
<?php partial_admin('page-header.php', [
    'title' => $existing !== null ? 'Edit ' . $existing['display_name'] : 'Add team member',
    'subtitle' => $existing !== null ? 'Changes apply the next time they open a page.' : 'They sign in at the same admin address with the username and password you set here, and see only the sections you allow.',
    'back' => '/admin/team',
    'back_label' => 'Team',
]); ?>
<?php if ($errors !== []): ?>
<div class="adm-summary" role="alert">Please check the highlighted fields.</div>
<?php endif; ?>
<form method="post" action="<?= e(url($action)) ?>" class="adm-form" novalidate autocomplete="off">
  <?= csrf_field() ?>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Person</h2>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'display_name', 'label' => 'Full name', 'value' => $form['display_name'], 'required' => true, 'maxlength' => 100, 'error' => $err('display_name')]); ?>
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'job_title', 'label' => 'Job title', 'value' => $form['job_title'], 'maxlength' => 80, 'placeholder' => 'e.g. Sales executive', 'error' => $err('job_title')]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'phone', 'name' => 'phone', 'label' => 'Phone', 'value' => $form['phone'], 'placeholder' => '0300 1234567', 'error' => $err('phone')]); ?>
      <?php partial_admin('field.php', ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'value' => $form['email'], 'error' => $err('email')]); ?>
    </div>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'cnic', 'label' => 'CNIC', 'value' => $form['cnic'], 'placeholder' => '35202-1234567-1', 'inputmode' => 'numeric', 'error' => $err('cnic')]); ?>
      <?php partial_admin('field.php', ['type' => 'date', 'name' => 'joined_on', 'label' => 'Joined on', 'value' => $form['joined_on'], 'error' => $err('joined_on')]); ?>
    </div>
    <?php partial_admin('field.php', ['type' => 'text', 'name' => 'address', 'label' => 'Address', 'value' => $form['address'], 'maxlength' => 255, 'error' => $err('address')]); ?>
    <?php partial_admin('field.php', ['type' => 'textarea', 'name' => 'notes', 'label' => 'Notes', 'value' => $form['notes'], 'rows' => 3, 'maxlength' => 1000, 'help' => 'Only super admins see this.', 'error' => $err('notes')]); ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Sign-in</h2>
    <div class="adm-field--pair">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'username', 'label' => 'Username', 'value' => $form['username'], 'required' => true, 'maxlength' => 64, 'attr' => ['autocapitalize' => 'none', 'spellcheck' => 'false', 'autocomplete' => 'off'], 'error' => $err('username')]); ?>
      <?php partial_admin('field.php', ['type' => 'password', 'name' => 'password', 'label' => $existing !== null ? 'New password' : 'Password', 'value' => '', 'required' => $existing === null, 'attr' => ['autocomplete' => 'new-password'], 'help' => $existing !== null ? 'Leave empty to keep their current password.' : 'At least ' . AUTH_PASSWORD_MIN_LENGTH . ' characters. Share it with them in person or on WhatsApp; they can change it after signing in.', 'error' => $err('password')]); ?>
    </div>
<?php if ($isSelf): ?>
    <input type="hidden" name="is_active" value="1">
    <p class="adm-note">This is your own account, so it always stays switched on.</p>
<?php else: ?>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'is_active', 'label' => 'Account', 'value' => $form['is_active'], 'options' => ['1' => 'Can sign in', '0' => 'Switched off'], 'help' => 'Switching off signs them out straight away. Their orders and history stay.', 'error' => $err('is_active')]); ?>
<?php endif; ?>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">What they can open</h2>
<?php if ($isSelf): ?>
    <input type="hidden" name="role" value="owner">
    <p class="adm-note">You are a super admin and can open everything.</p>
<?php else: ?>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'role', 'label' => 'Access', 'value' => $form['role'], 'options' => ['staff' => 'Only what I tick', 'owner' => 'Super admin (everything)'], 'help' => 'Super admins can also manage the team, set shares and mark shares as paid.', 'error' => $err('role')]); ?>
<?php endif; ?>
    <div data-show-when="role=staff">
      <?php partial_admin('field.php', ['type' => 'checkgroup', 'name' => 'permissions', 'label' => 'Sections', 'value' => $form['permissions'], 'options' => $moduleOptions, 'error' => $err('permissions')]); ?>
    </div>
  </div>
  <div class="adm-form__section">
    <h2 class="adm-form__section-title">Sales share</h2>
    <?php partial_admin('field.php', ['type' => 'segment', 'name' => 'is_seller', 'label' => 'Salesperson', 'value' => $form['is_seller'], 'options' => ['0' => 'No share', '1' => 'Earns a share of profit']]); ?>
    <div data-show-when="is_seller=1">
      <?php partial_admin('field.php', ['type' => 'text', 'name' => 'commission_rate', 'label' => 'Share of profit (%)', 'value' => $form['commission_rate'], 'inputmode' => 'decimal', 'placeholder' => '30', 'error' => $err('commission_rate'), 'help' => 'For each order credited to them: profit = what the customer paid for the perfumes after discount, minus the cost per bottle. Rs. 100 profit at 30% = Rs. 30 for them. A new percent applies to orders credited from now on; you can still change any single order.']); ?>
    </div>
  </div>
<?php partial_admin('action-bar.php', [
    'note' => $existing !== null ? 'Every change is recorded in the activity log.' : 'They can sign in as soon as you save.',
    'buttons' => [
        ['label' => 'Save', 'variant' => 'gold', 'attr' => ['name' => 'action', 'value' => 'save']],
        ['label' => 'Cancel', 'href' => '/admin/team', 'variant' => 'text'],
    ],
]); ?>
</form>
