<?php

namespace App\Model;

/**
 * @Index(name='email_idx', columns='email')
 */
class User extends \Phalcon\Mvc\Model
{
    /**
     * @Primary
     * @Identity
     * @Column(type='integer', nullable=false, autoIncrement=true)
     */
    public $id;

    /**
     * @Column(type='string', size=255, nullable=false)
     */
    public $email;

    /**
     * @Column(column='email_slug', type='string', size=255, nullable=false, unique=true)
     */
    public $emailSlug;

    /**
     * @Column(type='string', size=2048, nullable=false)
     */
    public $password;

    /**
     * @Column(column='created_at', type='datetime', size=255, sql_default='NOW()')
     */
    public $createdAt;

    /**
     * @Column(column='group_id', type='integer', nullable=true)
     * @BelongsTo(\App\Model\Group, 'id')
     */
    public $groupId;

    /**
     * @Column(type='number', size=255, index='fields_idx')
     */
    public $field_one;

    /**
     * @Column(type='number', size=255, index='fields_idx')
     */
    public $field_two;
}