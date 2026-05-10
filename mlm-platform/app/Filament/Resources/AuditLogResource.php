<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('actor_id')
                    ->relationship('actor', 'id'),
                Forms\Components\TextInput::make('actor_role'),
                Forms\Components\TextInput::make('target_type'),
                Forms\Components\TextInput::make('target_id')
                    ->numeric(),
                Forms\Components\TextInput::make('action')
                    ->required(),
                Forms\Components\Textarea::make('old_value')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('new_value')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address'),
                Forms\Components\TextInput::make('user_agent'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('actor.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actor_role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('target_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_agent')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
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
            'index' => Pages\ListAuditLogs::route('/'),
            'create' => Pages\CreateAuditLog::route('/create'),
            'edit' => Pages\EditAuditLog::route('/{record}/edit'),
        ];
    }
}
