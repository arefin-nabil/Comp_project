<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Filament\Resources\WithdrawalResource\RelationManagers;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ulid')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->relationship('branch', 'id'),
                Forms\Components\TextInput::make('requested_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('vat_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('payable_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('branch_split_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('company_split_amount')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('gateway'),
                Forms\Components\TextInput::make('account_number'),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Toggle::make('otp_verified')
                    ->required(),
                Forms\Components\TextInput::make('approved_by')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('approved_at'),
                Forms\Components\TextInput::make('wallet_transaction_id')
                    ->numeric(),
                Forms\Components\TextInput::make('gateway_transaction_id'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('idempotency_key'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ulid')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('requested_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vat_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payable_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch_split_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_split_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\IconColumn::make('otp_verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('approved_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet_transaction_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway_transaction_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('idempotency_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawals::route('/'),
            'create' => Pages\CreateWithdrawal::route('/create'),
            'edit' => Pages\EditWithdrawal::route('/{record}/edit'),
        ];
    }
}
