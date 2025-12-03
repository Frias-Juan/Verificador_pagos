<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UsersResource\Pages;
use App\Filament\Resources\UsersResource\RelationManagers;
use App\Models\User;
use App\Models\Users;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PhpParser\Node\Stmt\Label;

class UsersResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->minLength(4)
                ->maxLength(100),
                TextInput::make('lastname')
                ->label('Apellido')
                ->required()
                ->minLength(4)
                ->maxLength(100),
                TextInput::make('email')
                ->label('Correo Electrónico')
                ->required()
                ->email(),
                TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->required(),
               Select::make('roles')
                ->relationship('roles', 'name')
                ->label('Rol')
                ->required()
                ->reactive(),
                Select::make('permissions')
                ->relationship('permissions', 'name')
                ->label('Permisos')
                ->required()
                ->multiple()
                ->preload()
                /*Section::make('Datos Admin')
                    ->relationship('tenants')
                    ->schema([
                    Grid::make(3)
                    ->schema([
                        Select::make('type_ident')
                            ->label('Tipo Doc.')
                            ->options([
                                'V' => 'V',
                                'E' => 'E',
                                'J' => 'J',
                                'G' => 'G',
                            ])
                            ->required()
                            ->columnSpan(1),
                TextInput::make('cedula')
                    ->label('Cedula/RIF')
                    ->required()
                    ->minLength(8)
                    ->maxLength(10)
                    // NOTA: También puedes usar ->visible(fn (Closure $get): bool => $get('role') === 'Admin'),
                            ])
                            ])*/
                            ]);
            
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                ->label('Nombre')
                ->searchable()
                ->sortable(),
                TextColumn::make('lastname')
                ->label('Apellido')
                ->searchable()
                ->sortable(),
                TextColumn::make('email')
                ->label('Email')
                ->searchable(),
                TextColumn::make('roles.name')
                ->label('Rol')
                ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->iconButton(),
                Tables\Actions\DeleteAction::make()
                ->visible(fn () => Filament::auth()->user()->hasRole('Superadmin'))
                ->iconButton()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): ?string
    {
    return 'Settings';
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            /*'create' => Pages\CreateUsers::route('/create'),
            'edit' => Pages\EditUsers::route('/{record}/edit'),*/
        ];
    }
}
