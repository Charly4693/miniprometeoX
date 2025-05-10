<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigMC extends Model
{
    use HasFactory;

    protected $table =  'config';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'local_id',
        'MoneySymbol',
        'MoneyLowLimitToCreate',
        'MoneyAdaptLowValuesOnCreation',
        'MoneyLimitThatNeedsAuthorization',
        'MoneyLimitAbsolute',
        'MoneyLimitInTypeBets',
        'MoneyDenomination',
        'RoundPartialPrizes',
        'RoundPartialPrizesValue',
        'NumberOfDigits',
        'NewTicketNumberFormat',
        'HeaderOfTicketNumber',
        'HoursBetweenAutopurges',
        'NumberOfEventsToAutopurge',
        'DaysToAutopurgeIfEventOlderThan',
        'LastAutopurgeTimestamp',
        'AvatarsCachePath',
        'AdvancedGUI',
        'ForceAllowExports',
        'ExpirationDate',
        'LastAutoexpireTimestamp',
        'TITOTitle',
        'TITOTicketType',
        'TITOStreet',
        'TITOPlace',
        'TITOCity',
        'TITOPostalCode',
        'TITODescription',
        'TITOExpirationType',
        'NumberOfItemsPerPage',
        'BackupDBPath',
        'HoursBetweenBackupDB',
        'DaysToKeepBackupDB',
        'Aux1Limit',
        'Aux2Limit',
        'Aux3Limit',
        'Aux4Limit',
        'Aux5Limit',
        'Aux6Limit',
        'Aux7Limit',
        'Aux8Limit',
        'Aux9Limit',
        'Aux10Limit',
        'HideOnTCFilter',
        'ShowCloseOnlyFromIPs',
        'AllowIPs',
        'BanIPs',
        'AutoAddIPsToBan',
        'AllowMACs',
        'BanMACs',
        'AutoAddMACsToBan',
        'AllowTicketTypes',
        'BanTicketTypes',
        'OnCloseTicketTypeFilter1',
        'OnCloseTicketTypeAllowIPs1',
        'OnCloseTicketTypeBanIPs1',
        'OnCloseTicketTypeIPCreation1',
        'OnCloseTicketTypeFilter2',
        'OnCloseTicketTypeAllowIPs2',
        'OnCloseTicketTypeBanIPs2',
        'OnCloseTicketTypeIPCreation2',
        'OnCloseTicketTypeFilter3',
        'OnCloseTicketTypeAllowIPs3',
        'OnCloseTicketTypeBanIPs3',
        'OnCloseTicketTypeIPCreation3',
        'OnCloseTicketTypeFilter4',
        'OnCloseTicketTypeAllowIPs4',
        'OnCloseTicketTypeBanIPs4',
        'OnCloseTicketTypeIPCreation4',
        'OnCloseTicketTypeFilter5',
        'OnCloseTicketTypeAllowIPs5',
        'OnCloseTicketTypeBanIPs5',
        'OnCloseTicketTypeIPCreation5',
    ];

    public function local()
    {
        return $this->hasMany(Local::class,'id');
    }
}
