<?php

namespace App\Enums;

/**
 * Stable identifiers of audited events. The string value is stored in audit_logs.action,
 * so existing values must never be renamed.
 */
enum AuditAction: string
{
    case LoginSucceeded = 'auth.login';
    case LoginFailed = 'auth.login_failed';
    case Logout = 'auth.logout';
    case Lockout = 'auth.lockout';
    case PasswordResetRequested = 'auth.password_reset_requested';
    case PasswordReset = 'auth.password_reset';
    case AccountBlocked = 'auth.account_blocked';

    case SchoolCreated = 'school.created';
    case SchoolUpdated = 'school.updated';
    case SchoolDeleted = 'school.deleted';

    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserRoleChanged = 'user.role_changed';
    case UserActivated = 'user.activated';
    case UserDeactivated = 'user.deactivated';
    case UserDeleted = 'user.deleted';
    case UserInvited = 'user.invited';
    case UsersImported = 'user.imported';

    case ClassroomCreated = 'classroom.created';
    case ClassroomUpdated = 'classroom.updated';
    case ClassroomDeleted = 'classroom.deleted';
    case ClassroomMemberAdded = 'classroom.member_added';
    case ClassroomMemberRemoved = 'classroom.member_removed';

    case CourseCreated = 'course.created';
    case CourseUpdated = 'course.updated';
    case CoursePublished = 'course.published';
    case CourseUnpublished = 'course.unpublished';
    case CourseArchived = 'course.archived';
    case CourseDeleted = 'course.deleted';
    case CourseAssigned = 'course.assigned';
    case CourseUnassigned = 'course.unassigned';
    case MaterialUploaded = 'material.uploaded';
    case MaterialDeleted = 'material.deleted';

    case QuizCreated = 'quiz.created';
    case QuizUpdated = 'quiz.updated';
    case QuizPublished = 'quiz.published';
    case QuizUnpublished = 'quiz.unpublished';
    case QuizArchived = 'quiz.archived';
    case QuizDeleted = 'quiz.deleted';

    case AttemptScoreChanged = 'attempt.score_changed';
    case DataExported = 'data.exported';

    case CertificateIssued = 'certificate.issued';
    case CertificateRevoked = 'certificate.revoked';
    case CertificateSettingsChanged = 'certificate.settings_changed';

    public function label(): string
    {
        return match ($this) {
            self::LoginSucceeded => __('Prihlásenie'),
            self::LoginFailed => __('Neúspešné prihlásenie'),
            self::Logout => __('Odhlásenie'),
            self::Lockout => __('Dočasné zablokovanie prihlásenia'),
            self::PasswordResetRequested => __('Žiadosť o obnovu hesla'),
            self::PasswordReset => __('Obnova hesla'),
            self::AccountBlocked => __('Prístup deaktivovaného účtu'),
            self::SchoolCreated => __('Vytvorenie školy'),
            self::SchoolUpdated => __('Úprava školy'),
            self::SchoolDeleted => __('Odstránenie školy'),
            self::UserCreated => __('Vytvorenie používateľa'),
            self::UserUpdated => __('Úprava používateľa'),
            self::UserRoleChanged => __('Zmena roly'),
            self::UserActivated => __('Aktivácia účtu'),
            self::UserDeactivated => __('Deaktivácia účtu'),
            self::UserDeleted => __('Odstránenie používateľa'),
            self::UserInvited => __('Odoslanie pozvánky'),
            self::UsersImported => __('Import používateľov'),
            self::ClassroomCreated => __('Vytvorenie triedy'),
            self::ClassroomUpdated => __('Úprava triedy'),
            self::ClassroomDeleted => __('Odstránenie triedy'),
            self::ClassroomMemberAdded => __('Pridanie člena triedy'),
            self::ClassroomMemberRemoved => __('Odobratie člena triedy'),
            self::CourseCreated => __('Vytvorenie kurzu'),
            self::CourseUpdated => __('Úprava kurzu'),
            self::CoursePublished => __('Publikovanie kurzu'),
            self::CourseUnpublished => __('Stiahnutie kurzu do konceptu'),
            self::CourseArchived => __('Archivácia kurzu'),
            self::CourseDeleted => __('Odstránenie kurzu'),
            self::CourseAssigned => __('Priradenie kurzu'),
            self::CourseUnassigned => __('Zrušenie priradenia kurzu'),
            self::MaterialUploaded => __('Nahranie materiálu'),
            self::MaterialDeleted => __('Odstránenie materiálu'),
            self::QuizCreated => __('Vytvorenie testu'),
            self::QuizUpdated => __('Úprava testu'),
            self::QuizPublished => __('Publikovanie testu'),
            self::QuizUnpublished => __('Stiahnutie testu do konceptu'),
            self::QuizArchived => __('Archivácia testu'),
            self::QuizDeleted => __('Odstránenie testu'),
            self::AttemptScoreChanged => __('Zmena výsledku učiteľom'),
            self::DataExported => __('Export údajov'),
            self::CertificateIssued => __('Vydanie certifikátu'),
            self::CertificateRevoked => __('Zrušenie certifikátu'),
            self::CertificateSettingsChanged => __('Zmena podmienok certifikátu'),
        };
    }
}
