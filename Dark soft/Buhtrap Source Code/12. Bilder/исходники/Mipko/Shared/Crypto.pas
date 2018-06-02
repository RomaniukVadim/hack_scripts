unit Crypto;

interface

uses
  Windows;

  function Encrypt(const InString: string; StartKey, MultKey, AddKey: Integer): string;
  function Decrypt(const InString: string; StartKey, MultKey, AddKey: Integer): string;


implementation

{ **** UBPFD *********** by delphibase.endimus.com ****
>> Шифрование строки

Предназначена для простого шифрование строк и паролей, ключ 96 бит, шифрование
симметричное.

Зависимости: UBPFD.decrypt
Автор:       Anatoly Podgoretsky, anatoly@podgoretsky.com, Johvi
Copyright:   (c) Anatoly Podgoretsky, 1996
Дата:        26 апреля 2002 г.
***************************************************** }
function Encrypt(const InString: string; StartKey, MultKey, AddKey: Integer): string;
var
  I: Integer;
  // Если поменять тип переменной I на Integer, то будет возможно
  // шифрование текста длиной более 255 символом - VID.
begin
  Result := '';
  for I := 1 to Length(InString) do
  begin
    Result := Result + CHAR(Byte(InString[I]) xor (StartKey shr 8));
    StartKey := (Byte(Result[I]) + StartKey) * MultKey + AddKey;
  end;
end;

{ **** UBPFD *********** by delphibase.endimus.com ****
>> Расшифровка строки

Предназначена для расшифровки строки, ранее зашифрованной фукцией UBPFD.Encrypt

Зависимости: UBPFD.Encrypt
Автор:       Anatoly Podgoretsky, anatoly@podgoretsky.com, Johvi
Copyright:   (c) Anatoly Podgoretsky, 1996
Дата:        26 апреля 2002 г.
***************************************************** }
function Decrypt(const InString: string; StartKey, MultKey, AddKey: Integer): string;
var
  I: Integer;
  // Если поменять тип переменной I на Integer, то будет возможно
  // шифрование текста длиной более 255 символом - VID.
begin
  Result := '';
  for I := 1 to Length(InString) do
  begin
    Result := Result + CHAR(Byte(InString[I]) xor (StartKey shr 8));
    StartKey := (Byte(InString[I]) + StartKey) * MultKey + AddKey;
  end;
end;

initialization
  Randomize;


end.
