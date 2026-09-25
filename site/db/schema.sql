SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key    VARCHAR(64)  NOT NULL,
  setting_value  TEXT         NULL,
  setting_group  VARCHAR(32)  NOT NULL DEFAULT 'general',
  updated_at     DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (setting_key),
  KEY idx_settings_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS admin_users (
  id                  INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  username            VARCHAR(64)         NOT NULL,
  email               VARCHAR(190)        NOT NULL,
  password_hash       VARCHAR(255)        NOT NULL,
  display_name        VARCHAR(100)        NOT NULL,
  is_active           TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  last_login_at       DATETIME            NULL DEFAULT NULL,
  last_login_ip_hash  CHAR(64)            NULL DEFAULT NULL,
  password_changed_at DATETIME            NULL DEFAULT NULL,
  known_devices       TEXT                NULL DEFAULT NULL,
  created_at          DATETIME            NOT NULL,
  updated_at          DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_admin_users_username (username),
  UNIQUE KEY uk_admin_users_email (email),
  KEY idx_admin_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS admin_login_attempts (
  id            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  ip_hash       CHAR(64)            NOT NULL,
  username      VARCHAR(64)         NOT NULL,
  attempted_at  DATETIME            NOT NULL,
  was_success   TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_admin_login_attempts_ip_time (ip_hash, attempted_at),
  KEY idx_admin_login_attempts_user_time (username, attempted_at),
  KEY idx_admin_login_attempts_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS admin_activity_log (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  admin_id        INT UNSIGNED  NULL DEFAULT NULL,
  admin_username  VARCHAR(64)   NULL DEFAULT NULL,
  entity_type     VARCHAR(32)   NULL DEFAULT NULL,
  entity_id       INT UNSIGNED  NULL DEFAULT NULL,
  action          VARCHAR(48)   NOT NULL,
  summary         VARCHAR(255)  NOT NULL,
  before_json     TEXT          NULL DEFAULT NULL,
  after_json      TEXT          NULL DEFAULT NULL,
  ip_hash         CHAR(64)      NULL DEFAULT NULL,
  user_agent      VARCHAR(255)  NULL DEFAULT NULL,
  created_at      DATETIME      NOT NULL,
  PRIMARY KEY (id),
  KEY idx_admin_activity_log_entity (entity_type, entity_id, created_at),
  KEY idx_admin_activity_log_admin_time (admin_id, created_at),
  KEY idx_admin_activity_log_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS collections (
  id               INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  name             VARCHAR(100)        NOT NULL,
  slug             VARCHAR(160)        NOT NULL,
  tagline          VARCHAR(160)        NULL DEFAULT NULL,
  description      TEXT                NULL DEFAULT NULL,
  mood             VARCHAR(255)        NULL DEFAULT NULL,
  image            VARCHAR(255)        NULL DEFAULT NULL,
  sort_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
  is_active        TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  show_on_home     TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  seo_title        VARCHAR(160)        NULL DEFAULT NULL,
  seo_description  VARCHAR(255)        NULL DEFAULT NULL,
  created_at       DATETIME            NOT NULL,
  updated_at       DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_collections_slug (slug),
  KEY idx_collections_active (is_active, sort_order),
  KEY idx_collections_home (is_active, show_on_home, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS scent_families (
  id               INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  name             VARCHAR(60)         NOT NULL,
  slug             VARCHAR(160)        NOT NULL,
  intro            TEXT                NULL DEFAULT NULL,
  sort_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
  is_active        TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  seo_title        VARCHAR(160)        NULL DEFAULT NULL,
  seo_description  VARCHAR(255)        NULL DEFAULT NULL,
  created_at       DATETIME            NOT NULL,
  updated_at       DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_scent_families_name (name),
  UNIQUE KEY uk_scent_families_slug (slug),
  KEY idx_scent_families_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS products (
  id                INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  collection_id     INT UNSIGNED        NULL DEFAULT NULL,
  name              VARCHAR(120)        NOT NULL,
  slug              VARCHAR(160)        NOT NULL,
  gender            VARCHAR(20)         NOT NULL DEFAULT 'unisex',
  scent_family      VARCHAR(60)         NULL DEFAULT NULL,
  short_description VARCHAR(255)        NULL DEFAULT NULL,
  description       TEXT                NULL DEFAULT NULL,
  notes_top         VARCHAR(255)        NULL DEFAULT NULL,
  notes_heart       VARCHAR(255)        NULL DEFAULT NULL,
  notes_base        VARCHAR(255)        NULL DEFAULT NULL,
  longevity         TINYINT UNSIGNED    NULL DEFAULT NULL,
  sillage           TINYINT UNSIGNED    NULL DEFAULT NULL,
  best_season       VARCHAR(60)         NULL DEFAULT NULL,
  occasion          VARCHAR(120)        NULL DEFAULT NULL,
  is_featured       TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  is_new            TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  is_active         TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  sort_order        SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
  sales_count       INT UNSIGNED        NOT NULL DEFAULT 0,
  rating_avg        DECIMAL(3,2)        NOT NULL DEFAULT 0.00,
  rating_count      INT UNSIGNED        NOT NULL DEFAULT 0,
  published_at      DATETIME            NULL DEFAULT NULL,
  deleted_at        DATETIME            NULL DEFAULT NULL,
  seo_title         VARCHAR(160)        NULL DEFAULT NULL,
  seo_description   VARCHAR(255)        NULL DEFAULT NULL,
  created_at        DATETIME            NOT NULL,
  updated_at        DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_products_slug (slug),
  KEY idx_products_listing (is_active, deleted_at, sort_order),
  KEY idx_products_collection (collection_id, is_active),
  KEY idx_products_gender (gender, is_active),
  KEY idx_products_family (scent_family, is_active),
  KEY idx_products_new (is_active, published_at),
  KEY idx_products_featured (is_featured, is_active),
  KEY idx_products_sales (is_active, sales_count),
  CONSTRAINT fk_products_collection_id FOREIGN KEY (collection_id)
    REFERENCES collections (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS product_sizes (
  id                  INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  product_id          INT UNSIGNED           NOT NULL,
  size_label          VARCHAR(20)            NOT NULL,
  size_ml             SMALLINT UNSIGNED      NULL DEFAULT NULL,
  sku                 VARCHAR(40)            NOT NULL,
  price               DECIMAL(10,2) UNSIGNED NOT NULL,
  sale_price          DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
  stock               INT UNSIGNED           NOT NULL DEFAULT 0,
  low_stock_threshold TINYINT UNSIGNED       NULL DEFAULT NULL,
  is_default          TINYINT UNSIGNED       NOT NULL DEFAULT 0,
  is_active           TINYINT UNSIGNED       NOT NULL DEFAULT 1,
  sort_order          SMALLINT UNSIGNED      NOT NULL DEFAULT 0,
  created_at          DATETIME               NOT NULL,
  updated_at          DATETIME               NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_product_sizes_sku (sku),
  KEY idx_product_sizes_product (product_id, is_active, sort_order),
  KEY idx_product_sizes_stock (stock),
  CONSTRAINT fk_product_sizes_product_id FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS product_images (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  product_id  INT UNSIGNED        NOT NULL,
  filename    VARCHAR(255)        NOT NULL,
  alt_text    VARCHAR(255)        NULL DEFAULT NULL,
  width       SMALLINT UNSIGNED   NULL DEFAULT NULL,
  height      SMALLINT UNSIGNED   NULL DEFAULT NULL,
  sort_order  SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
  is_primary  TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  created_at  DATETIME            NOT NULL,
  PRIMARY KEY (id),
  KEY idx_product_images_product (product_id, sort_order),
  KEY idx_product_images_primary (product_id, is_primary),
  CONSTRAINT fk_product_images_product_id FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS reviews (
  id            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  product_id    INT UNSIGNED        NOT NULL,
  customer_name VARCHAR(100)        NOT NULL,
  customer_city VARCHAR(80)         NULL DEFAULT NULL,
  rating        TINYINT UNSIGNED    NOT NULL,
  title         VARCHAR(160)        NULL DEFAULT NULL,
  body          TEXT                NULL DEFAULT NULL,
  status        VARCHAR(20)         NOT NULL DEFAULT 'pending',
  is_sample     TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  ip_hash       CHAR(64)            NULL DEFAULT NULL,
  moderated_by  INT UNSIGNED        NULL DEFAULT NULL,
  created_at    DATETIME            NOT NULL,
  approved_at   DATETIME            NULL DEFAULT NULL,
  rejected_at   DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_reviews_public (product_id, status, created_at),
  KEY idx_reviews_queue (status, created_at),
  KEY idx_reviews_sample (is_sample),
  CONSTRAINT fk_reviews_product_id FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_moderated_by FOREIGN KEY (moderated_by)
    REFERENCES admin_users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS content_pages (
  id               INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  slug             VARCHAR(160)        NOT NULL,
  title            VARCHAR(160)        NOT NULL,
  heading          VARCHAR(160)        NULL DEFAULT NULL,
  body             MEDIUMTEXT          NOT NULL,
  body_format      VARCHAR(20)         NOT NULL DEFAULT 'html',
  template         VARCHAR(30)         NOT NULL DEFAULT 'page',
  is_system        TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  is_active        TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  sort_order       SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
  seo_title        VARCHAR(160)        NULL DEFAULT NULL,
  seo_description  VARCHAR(255)        NULL DEFAULT NULL,
  created_at       DATETIME            NOT NULL,
  updated_at       DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_content_pages_slug (slug),
  KEY idx_content_pages_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS slug_redirects (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  entity_type  VARCHAR(20)   NOT NULL,
  old_slug     VARCHAR(160)  NOT NULL,
  new_slug     VARCHAR(160)  NOT NULL,
  entity_id    INT UNSIGNED  NULL DEFAULT NULL,
  hit_count    INT UNSIGNED  NOT NULL DEFAULT 0,
  created_at   DATETIME      NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_slug_redirects_lookup (entity_type, old_slug),
  KEY idx_slug_redirects_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS contact_messages (
  id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NULL DEFAULT NULL,
  phone         VARCHAR(32)   NULL DEFAULT NULL,
  subject       VARCHAR(160)  NULL DEFAULT NULL,
  order_number  VARCHAR(20)   NULL DEFAULT NULL,
  message       TEXT          NOT NULL,
  status        VARCHAR(20)   NOT NULL DEFAULT 'new',
  admin_note    TEXT          NULL DEFAULT NULL,
  ip_hash       CHAR(64)      NULL DEFAULT NULL,
  created_at    DATETIME      NOT NULL,
  read_at       DATETIME      NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_contact_messages_status (status, created_at),
  KEY idx_contact_messages_created (created_at),
  KEY idx_contact_messages_order (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  email            VARCHAR(190)  NOT NULL,
  status           VARCHAR(20)   NOT NULL DEFAULT 'subscribed',
  source           VARCHAR(32)   NOT NULL DEFAULT 'footer',
  unsub_token      CHAR(40)      NOT NULL,
  ip_hash          CHAR(64)      NULL DEFAULT NULL,
  subscribed_at    DATETIME      NOT NULL,
  unsubscribed_at  DATETIME      NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_newsletter_subscribers_email (email),
  KEY idx_newsletter_subscribers_status (status, subscribed_at),
  KEY idx_newsletter_subscribers_token (unsub_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS quiz_questions (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  question    VARCHAR(255)        NOT NULL,
  helper_text VARCHAR(255)        NULL DEFAULT NULL,
  step        TINYINT UNSIGNED    NOT NULL,
  is_active   TINYINT UNSIGNED    NOT NULL DEFAULT 1,
  created_at  DATETIME            NOT NULL,
  updated_at  DATETIME            NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_quiz_questions_step (step),
  KEY idx_quiz_questions_active (is_active, step)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS quiz_options (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  question_id INT UNSIGNED      NOT NULL,
  label       VARCHAR(120)      NOT NULL,
  sublabel    VARCHAR(160)      NULL DEFAULT NULL,
  option_code CHAR(1)           NOT NULL,
  image       VARCHAR(255)      NULL DEFAULT NULL,
  sort_order  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uk_quiz_options_code (question_id, option_code),
  KEY idx_quiz_options_question (question_id, sort_order),
  CONSTRAINT fk_quiz_options_question_id FOREIGN KEY (question_id)
    REFERENCES quiz_questions (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS quiz_option_scores (
  id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  option_id       INT UNSIGNED     NOT NULL,
  scent_family_id INT UNSIGNED     NOT NULL,
  weight          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uk_quiz_option_scores_pair (option_id, scent_family_id),
  KEY idx_quiz_option_scores_family (scent_family_id),
  CONSTRAINT fk_quiz_option_scores_option_id FOREIGN KEY (option_id)
    REFERENCES quiz_options (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_quiz_option_scores_scent_family_id FOREIGN KEY (scent_family_id)
    REFERENCES scent_families (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS coupons (
  id               INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  code             VARCHAR(40)            NOT NULL,
  type             VARCHAR(10)            NOT NULL,
  value            DECIMAL(10,2) UNSIGNED NOT NULL,
  min_order_total  DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  usage_limit      INT UNSIGNED           NULL DEFAULT NULL,
  per_phone_limit  SMALLINT UNSIGNED      NULL DEFAULT NULL,
  used_count       INT UNSIGNED           NOT NULL DEFAULT 0,
  starts_at        DATETIME               NULL DEFAULT NULL,
  expires_at       DATETIME               NULL DEFAULT NULL,
  is_active        TINYINT UNSIGNED       NOT NULL DEFAULT 1,
  created_at       DATETIME               NOT NULL,
  updated_at       DATETIME               NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_coupons_code (code),
  KEY idx_coupons_active (is_active, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS orders (
  id                       INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  order_number             VARCHAR(16)            NOT NULL,
  access_token             CHAR(32)               NOT NULL,
  idempotency_key          CHAR(32)               NOT NULL,
  status                   VARCHAR(16)            NOT NULL DEFAULT 'pending',
  payment_method           VARCHAR(16)            NOT NULL,
  payment_status           VARCHAR(24)            NOT NULL DEFAULT 'unpaid',
  customer_name            VARCHAR(120)           NOT NULL,
  customer_phone           VARCHAR(24)            NOT NULL,
  phone_normalized         VARCHAR(16)            NOT NULL,
  customer_email           VARCHAR(190)           NULL DEFAULT NULL,
  city                     VARCHAR(80)            NOT NULL,
  address                  VARCHAR(400)           NOT NULL,
  postal_code              VARCHAR(12)            NULL DEFAULT NULL,
  customer_note            VARCHAR(500)           NULL DEFAULT NULL,
  subtotal                 DECIMAL(10,2) UNSIGNED NOT NULL,
  discount_total           DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  shipping_fee             DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  cod_fee                  DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  grand_total              DECIMAL(10,2) UNSIGNED NOT NULL,
  item_count               SMALLINT UNSIGNED      NOT NULL DEFAULT 0,
  coupon_id                INT UNSIGNED           NULL DEFAULT NULL,
  coupon_code              VARCHAR(40)            NULL DEFAULT NULL,
  coupon_type              VARCHAR(10)            NULL DEFAULT NULL,
  coupon_value             DECIMAL(10,2)          NULL DEFAULT NULL,
  payment_reference        VARCHAR(64)            NULL DEFAULT NULL,
  payment_account_snapshot VARCHAR(160)           NULL DEFAULT NULL,
  paid_at                  DATETIME               NULL DEFAULT NULL,
  courier_name             VARCHAR(60)            NULL DEFAULT NULL,
  tracking_number          VARCHAR(60)            NULL DEFAULT NULL,
  tracking_url             VARCHAR(255)           NULL DEFAULT NULL,
  shipped_at               DATETIME               NULL DEFAULT NULL,
  delivered_at             DATETIME               NULL DEFAULT NULL,
  cancelled_at             DATETIME               NULL DEFAULT NULL,
  cancel_reason            VARCHAR(200)           NULL DEFAULT NULL,
  stock_restored_at        DATETIME               NULL DEFAULT NULL,
  ip_hash                  CHAR(64)               NULL DEFAULT NULL,
  user_agent               VARCHAR(255)           NULL DEFAULT NULL,
  source                   VARCHAR(20)            NOT NULL DEFAULT 'web',
  created_at               DATETIME               NOT NULL,
  updated_at               DATETIME               NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_orders_number (order_number),
  UNIQUE KEY uk_orders_idempotency (idempotency_key),
  KEY idx_orders_status_created (status, created_at),
  KEY idx_orders_payment (payment_method, payment_status),
  KEY idx_orders_track (order_number, phone_normalized),
  KEY idx_orders_phone_created (phone_normalized, created_at),
  KEY idx_orders_created (created_at),
  CONSTRAINT fk_orders_coupon_id FOREIGN KEY (coupon_id)
    REFERENCES coupons (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS order_items (
  id                  INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  order_id            INT UNSIGNED           NOT NULL,
  product_id          INT UNSIGNED           NULL DEFAULT NULL,
  product_size_id     INT UNSIGNED           NULL DEFAULT NULL,
  product_name        VARCHAR(160)           NOT NULL,
  product_slug        VARCHAR(180)           NOT NULL,
  size_label          VARCHAR(32)            NOT NULL,
  size_ml             SMALLINT UNSIGNED      NULL DEFAULT NULL,
  sku                 VARCHAR(48)            NOT NULL,
  image_filename      VARCHAR(255)           NULL DEFAULT NULL,
  unit_price          DECIMAL(10,2) UNSIGNED NOT NULL,
  sale_price          DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
  unit_price_charged  DECIMAL(10,2) UNSIGNED NOT NULL,
  quantity            SMALLINT UNSIGNED      NOT NULL,
  line_total          DECIMAL(10,2) UNSIGNED NOT NULL,
  line_discount       DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00,
  created_at          DATETIME               NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  KEY idx_order_items_size (product_size_id),
  CONSTRAINT fk_order_items_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_order_items_product_id FOREIGN KEY (product_id)
    REFERENCES products (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_order_items_product_size_id FOREIGN KEY (product_size_id)
    REFERENCES product_sizes (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS order_status_history (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id       INT UNSIGNED NOT NULL,
  field          VARCHAR(16)  NOT NULL DEFAULT 'status',
  from_status    VARCHAR(24)  NULL DEFAULT NULL,
  to_status      VARCHAR(24)  NOT NULL,
  note           VARCHAR(500) NULL DEFAULT NULL,
  changed_by     VARCHAR(16)  NOT NULL,
  admin_id       INT UNSIGNED NULL DEFAULT NULL,
  admin_username VARCHAR(64)  NULL DEFAULT NULL,
  ip_hash        CHAR(64)     NULL DEFAULT NULL,
  created_at     DATETIME     NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_status_history_order_created (order_id, created_at),
  KEY idx_order_status_history_to_created (to_status, created_at),
  CONSTRAINT fk_order_status_history_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS payment_proofs (
  id                INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  order_id          INT UNSIGNED           NOT NULL,
  file_path         VARCHAR(160)           NULL DEFAULT NULL,
  original_name     VARCHAR(255)           NULL DEFAULT NULL,
  mime_type         VARCHAR(40)            NOT NULL,
  byte_size         INT UNSIGNED           NOT NULL,
  sha256            CHAR(64)               NOT NULL,
  transaction_ref   VARCHAR(64)            NULL DEFAULT NULL,
  sender_name       VARCHAR(120)           NULL DEFAULT NULL,
  amount_claimed    DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL,
  review_status     VARCHAR(20)            NOT NULL DEFAULT 'pending',
  reviewed_by       INT UNSIGNED           NULL DEFAULT NULL,
  reviewed_by_name  VARCHAR(64)            NULL DEFAULT NULL,
  reviewed_at       DATETIME               NULL DEFAULT NULL,
  review_note       VARCHAR(255)           NULL DEFAULT NULL,
  uploaded_ip_hash  CHAR(64)               NULL DEFAULT NULL,
  purged_at         DATETIME               NULL DEFAULT NULL,
  created_at        DATETIME               NOT NULL,
  PRIMARY KEY (id),
  KEY idx_payment_proofs_order (order_id, created_at),
  KEY idx_payment_proofs_review (review_status, created_at),
  KEY idx_payment_proofs_sha (sha256),
  KEY idx_payment_proofs_ref (transaction_ref),
  CONSTRAINT fk_payment_proofs_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS coupon_redemptions (
  id               INT UNSIGNED           NOT NULL AUTO_INCREMENT,
  coupon_id        INT UNSIGNED           NOT NULL,
  order_id         INT UNSIGNED           NOT NULL,
  code             VARCHAR(40)            NOT NULL,
  phone_normalized VARCHAR(16)            NOT NULL,
  discount_amount  DECIMAL(10,2) UNSIGNED NOT NULL,
  order_subtotal   DECIMAL(10,2) UNSIGNED NOT NULL,
  status           VARCHAR(12)            NOT NULL DEFAULT 'applied',
  created_at       DATETIME               NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_coupon_redemptions_order (order_id),
  KEY idx_coupon_redemptions_coupon_phone (coupon_id, phone_normalized, status),
  KEY idx_coupon_redemptions_coupon_status (coupon_id, status),
  CONSTRAINT fk_coupon_redemptions_coupon_id FOREIGN KEY (coupon_id)
    REFERENCES coupons (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_coupon_redemptions_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS order_notes (
  id           INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  order_id     INT UNSIGNED        NOT NULL,
  body         VARCHAR(1000)       NOT NULL,
  author_type  VARCHAR(16)         NOT NULL DEFAULT 'admin',
  admin_id     INT UNSIGNED        NULL DEFAULT NULL,
  admin_name   VARCHAR(64)         NULL DEFAULT NULL,
  is_pinned    TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  created_at   DATETIME            NOT NULL,
  PRIMARY KEY (id),
  KEY idx_order_notes_order_created (order_id, created_at),
  CONSTRAINT fk_order_notes_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS email_outbox (
  id           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  template     VARCHAR(40)      NOT NULL,
  recipient    VARCHAR(190)     NOT NULL,
  subject      VARCHAR(190)     NOT NULL,
  body_html    MEDIUMTEXT       NOT NULL,
  order_id     INT UNSIGNED     NULL DEFAULT NULL,
  status       VARCHAR(20)      NOT NULL DEFAULT 'queued',
  attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  last_error   VARCHAR(255)     NULL DEFAULT NULL,
  next_try_at  DATETIME         NOT NULL,
  sent_at      DATETIME         NULL DEFAULT NULL,
  created_at   DATETIME         NOT NULL,
  PRIMARY KEY (id),
  KEY idx_email_outbox_due (status, next_try_at),
  KEY idx_email_outbox_order (order_id),
  CONSTRAINT fk_email_outbox_order_id FOREIGN KEY (order_id)
    REFERENCES orders (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS rate_limits (
  id            INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  bucket        VARCHAR(40)         NOT NULL,
  subject_hash  CHAR(64)            NOT NULL,
  attempted_at  DATETIME            NOT NULL,
  was_success   TINYINT UNSIGNED    NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_rate_limits_lookup (bucket, subject_hash, attempted_at),
  KEY idx_rate_limits_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
