<?php

/**
 * File was auto-generated
 */

namespace App\Migrations;

use App\Helper\Migration\Script as MigrationScript;

class Migration_20250526130601 extends MigrationScript
{
    protected function up()
    {
        $this->db->query('CREATE TABLE IF NOT EXISTS "public"."group" (
            "id" SERIAL NOT NULL,
            "name" CHARACTER VARYING(255) NOT NULL,
            PRIMARY KEY ("id"),
            CONSTRAINT "name_uniq" UNIQUE ("name")
        )');

        $this->db->query('CREATE TABLE IF NOT EXISTS "public"."user" (
            "id" SERIAL NOT NULL,
            "email" CHARACTER VARYING(255) NOT NULL,
            "email_slug" CHARACTER VARYING(255) NOT NULL,
            "password" CHARACTER VARYING(2048) NOT NULL,
            "created_at" TIMESTAMP NOT NULL,
            "group_id" INT NOT NULL,
            "field_one" CHARACTER VARYING(255) NOT NULL,
            "field_two" CHARACTER VARYING(255) NOT NULL,
            PRIMARY KEY ("id"),
            CONSTRAINT "email_slug_uniq" UNIQUE ("email_slug")
        )');

        $this->db->query('CREATE INDEX IF NOT EXISTS "fields_idx" ON "public"."user" ("field_one", "field_two")');

        $this->db->query('ALTER TABLE "public"."user" DROP CONSTRAINT IF EXISTS "user_group_fkey"');
        $this->db->query('ALTER TABLE "public"."user" ADD CONSTRAINT "user_group_fkey" FOREIGN KEY ("group_id") REFERENCES "group" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT');
    }

    protected function down()
    {
        $this->db->query('ALTER TABLE "user" DROP CONSTRAINT IF EXISTS "user_group_fkey"');
        $this->db->query('DROP INDEX IF EXISTS "fields_idx"');
        $this->db->query('DROP TABLE IF EXISTS "user"');
        $this->db->query('DROP TABLE IF EXISTS "group"');
    }

    public function getVersion(): int
    {
        return intval("20250526130601");
    }
}
