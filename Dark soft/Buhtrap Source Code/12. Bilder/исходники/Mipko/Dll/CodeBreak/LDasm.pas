{
  ---------------------------------------------------
  Opcode Length Disassembler.
  Coded By Ms-Rem ( Ms-Rem@yandex.ru ) ICQ 286370715
  ---------------------------------------------------
  12.08.2005 - fixed many bugs...
  09.08.2005 - fixed bug with 0F BA opcode.
  07.08.2005 - added SSE, SSE2, SSE3 and 3Dnow instruction support.
  06.08.2005 - fixed bug with F6 and F7 opcodes.
  29.07.2005 - fixed bug with OP_WORD opcodes.
}
unit LDasm;

interface

type
 dword  = cardinal;
 ppbyte = ^pbyte;

function SizeOfCode(Code: pointer; pOpcode: ppbyte): dword;
function SizeOfProc(Proc: pointer): dword;
function IsRelativeCmd(pOpcode: pbyte): boolean;

implementation

const
 OP_NONE          = $00;
 OP_MODRM         = $01;
 OP_DATA_I8       = $02;
 OP_DATA_I16      = $04;
 OP_DATA_I32      = $08;
 OP_DATA_PRE66_67 = $10;
 OP_WORD          = $20;
 OP_REL32         = $40;


const
 OpcodeFlags: array [$00..$FF] of byte =
 (
  OP_MODRM,                        // 00
  OP_MODRM,                        // 01
  OP_MODRM,                        // 02
  OP_MODRM,                        // 03
  OP_DATA_I8,                      // 04
  OP_DATA_PRE66_67,                // 05
  OP_NONE,                         // 06
  OP_NONE,                         // 07
  OP_MODRM,                        // 08
  OP_MODRM,                        // 09
  OP_MODRM,                        // 0A
  OP_MODRM,                        // 0B
  OP_DATA_I8,                      // 0C
  OP_DATA_PRE66_67,                // 0D
  OP_NONE,                         // 0E
  OP_NONE,                         // 0F
  OP_MODRM,                        // 10
  OP_MODRM,                        // 11
  OP_MODRM,                        // 12
  OP_MODRM,                        // 13
  OP_DATA_I8,                      // 14
  OP_DATA_PRE66_67,                // 15
  OP_NONE,                         // 16
  OP_NONE,                         // 17
  OP_MODRM,                        // 18
  OP_MODRM,                        // 19
  OP_MODRM,                        // 1A
  OP_MODRM,                        // 1B
  OP_DATA_I8,                      // 1C
  OP_DATA_PRE66_67,                // 1D
  OP_NONE,                         // 1E
  OP_NONE,                         // 1F
  OP_MODRM,                        // 20
  OP_MODRM,                        // 21
  OP_MODRM,                        // 22
  OP_MODRM,                        // 23
  OP_DATA_I8,                      // 24
  OP_DATA_PRE66_67,                // 25
  OP_NONE,                         // 26
  OP_NONE,                         // 27
  OP_MODRM,                        // 28
  OP_MODRM,                        // 29
  OP_MODRM,                        // 2A
  OP_MODRM,                        // 2B
  OP_DATA_I8,                      // 2C
  OP_DATA_PRE66_67,                // 2D
  OP_NONE,                         // 2E
  OP_NONE,                         // 2F
  OP_MODRM,                        // 30
  OP_MODRM,                        // 31
  OP_MODRM,                        // 32
  OP_MODRM,                        // 33
  OP_DATA_I8,                      // 34
  OP_DATA_PRE66_67,                // 35
  OP_NONE,                         // 36
  OP_NONE,                         // 37
  OP_MODRM,                        // 38
  OP_MODRM,                        // 39
  OP_MODRM,                        // 3A
  OP_MODRM,                        // 3B
  OP_DATA_I8,                      // 3C
  OP_DATA_PRE66_67,                // 3D
  OP_NONE,                         // 3E
  OP_NONE,                         // 3F
  OP_NONE,                         // 40
  OP_NONE,                         // 41
  OP_NONE,                         // 42
  OP_NONE,                         // 43
  OP_NONE,                         // 44
  OP_NONE,                         // 45
  OP_NONE,                         // 46
  OP_NONE,                         // 47
  OP_NONE,                         // 48
  OP_NONE,                         // 49
  OP_NONE,                         // 4A
  OP_NONE,                         // 4B
  OP_NONE,                         // 4C
  OP_NONE,                         // 4D
  OP_NONE,                         // 4E
  OP_NONE,                         // 4F
  OP_NONE,                         // 50
  OP_NONE,                         // 51
  OP_NONE,                         // 52
  OP_NONE,                         // 53
  OP_NONE,                         // 54
  OP_NONE,                         // 55
  OP_NONE,                         // 56
  OP_NONE,                         // 57
  OP_NONE,                         // 58
  OP_NONE,                         // 59
  OP_NONE,                         // 5A
  OP_NONE,                         // 5B
  OP_NONE,                         // 5C
  OP_NONE,                         // 5D
  OP_NONE,                         // 5E
  OP_NONE,                         // 5F
  OP_NONE,                         // 60
  OP_NONE,                         // 61
  OP_MODRM,                        // 62
  OP_MODRM,                        // 63
  OP_NONE,                         // 64
  OP_NONE,                         // 65
  OP_NONE,                         // 66
  OP_NONE,                         // 67
  OP_DATA_PRE66_67,                // 68
  OP_MODRM or OP_DATA_PRE66_67,    // 69
  OP_DATA_I8,                      // 6A
  OP_MODRM or OP_DATA_I8,          // 6B
  OP_NONE,                         // 6C
  OP_NONE,                         // 6D
  OP_NONE,                         // 6E
  OP_NONE,                         // 6F
  OP_DATA_I8,                      // 70
  OP_DATA_I8,                      // 71
  OP_DATA_I8,                      // 72
  OP_DATA_I8,                      // 73
  OP_DATA_I8,                      // 74
  OP_DATA_I8,                      // 75
  OP_DATA_I8,                      // 76
  OP_DATA_I8,                      // 77
  OP_DATA_I8,                      // 78
  OP_DATA_I8,                      // 79
  OP_DATA_I8,                      // 7A
  OP_DATA_I8,                      // 7B
  OP_DATA_I8,                      // 7C
  OP_DATA_I8,                      // 7D
  OP_DATA_I8,                      // 7E
  OP_DATA_I8,                      // 7F
  OP_MODRM or OP_DATA_I8,          // 80
  OP_MODRM or OP_DATA_PRE66_67,    // 81
  OP_MODRM or OP_DATA_I8,          // 82
  OP_MODRM or OP_DATA_I8,          // 83
  OP_MODRM,                        // 84
  OP_MODRM,                        // 85
  OP_MODRM,                        // 86
  OP_MODRM,                        // 87
  OP_MODRM,                        // 88
  OP_MODRM,                        // 89
  OP_MODRM,                        // 8A
  OP_MODRM,                        // 8B
  OP_MODRM,                        // 8C
  OP_MODRM,                        // 8D
  OP_MODRM,                        // 8E
  OP_MODRM,                        // 8F
  OP_NONE,                         // 90
  OP_NONE,                         // 91
  OP_NONE,                         // 92
  OP_NONE,                         // 93
  OP_NONE,                         // 94
  OP_NONE,                         // 95
  OP_NONE,                         // 96
  OP_NONE,                         // 97
  OP_NONE,                         // 98
  OP_NONE,                         // 99
  OP_DATA_I16 or OP_DATA_PRE66_67, // 9A
  OP_NONE,                         // 9B
  OP_NONE,                         // 9C
  OP_NONE,                         // 9D
  OP_NONE,                         // 9E
  OP_NONE,                         // 9F
  OP_DATA_PRE66_67,                // A0
  OP_DATA_PRE66_67,                // A1
  OP_DATA_PRE66_67,                // A2
  OP_DATA_PRE66_67,                // A3
  OP_NONE,                         // A4
  OP_NONE,                         // A5
  OP_NONE,                         // A6
  OP_NONE,                         // A7
  OP_DATA_I8,                      // A8
  OP_DATA_PRE66_67,                // A9
  OP_NONE,                         // AA
  OP_NONE,                         // AB
  OP_NONE,                         // AC
  OP_NONE,                         // AD
  OP_NONE,                         // AE
  OP_NONE,                         // AF
  OP_DATA_I8,                      // B0
  OP_DATA_I8,                      // B1
  OP_DATA_I8,                      // B2
  OP_DATA_I8,                      // B3
  OP_DATA_I8,                      // B4
  OP_DATA_I8,                      // B5
  OP_DATA_I8,                      // B6
  OP_DATA_I8,                      // B7
  OP_DATA_PRE66_67,                // B8
  OP_DATA_PRE66_67,                // B9
  OP_DATA_PRE66_67,                // BA
  OP_DATA_PRE66_67,                // BB
  OP_DATA_PRE66_67,                // BC
  OP_DATA_PRE66_67,                // BD
  OP_DATA_PRE66_67,                // BE
  OP_DATA_PRE66_67,                // BF
  OP_MODRM or OP_DATA_I8,          // C0
  OP_MODRM or OP_DATA_I8,          // C1
  OP_DATA_I16,                     // C2
  OP_NONE,                         // C3
  OP_MODRM,                        // C4
  OP_MODRM,                        // C5
  OP_MODRM or OP_DATA_I8,          // C6
  OP_MODRM or OP_DATA_PRE66_67,    // C7
  OP_DATA_I8 or OP_DATA_I16,       // C8
  OP_NONE,                         // C9
  OP_DATA_I16,                     // CA
  OP_NONE,                         // CB
  OP_NONE,                         // CC
  OP_DATA_I8,                      // CD
  OP_NONE,                         // CE
  OP_NONE,                         // CF
  OP_MODRM,                        // D0
  OP_MODRM,                        // D1
  OP_MODRM,                        // D2
  OP_MODRM,                        // D3
  OP_DATA_I8,                      // D4
  OP_DATA_I8,                      // D5
  OP_NONE,                         // D6
  OP_NONE,                         // D7
  OP_WORD,                         // D8
  OP_WORD,                         // D9
  OP_WORD,                         // DA
  OP_WORD,                         // DB
  OP_WORD,                         // DC
  OP_WORD,                         // DD
  OP_WORD,                         // DE
  OP_WORD,                         // DF
  OP_DATA_I8,                      // E0
  OP_DATA_I8,                      // E1
  OP_DATA_I8,                      // E2
  OP_DATA_I8,                      // E3
  OP_DATA_I8,                      // E4
  OP_DATA_I8,                      // E5
  OP_DATA_I8,                      // E6
  OP_DATA_I8,                      // E7
  OP_DATA_PRE66_67 or OP_REL32,    // E8
  OP_DATA_PRE66_67 or OP_REL32,    // E9
  OP_DATA_I16 or OP_DATA_PRE66_67, // EA
  OP_DATA_I8,                      // EB
  OP_NONE,                         // EC
  OP_NONE,                         // ED
  OP_NONE,                         // EE
  OP_NONE,                         // EF
  OP_NONE,                         // F0
  OP_NONE,                         // F1
  OP_NONE,                         // F2
  OP_NONE,                         // F3
  OP_NONE,                         // F4
  OP_NONE,                         // F5
  OP_MODRM,                        // F6
  OP_MODRM,                        // F7
  OP_NONE,                         // F8
  OP_NONE,                         // F9
  OP_NONE,                         // FA
  OP_NONE,                         // FB
  OP_NONE,                         // FC
  OP_NONE,                         // FD
  OP_MODRM,                        // FE
  OP_MODRM or OP_REL32             // FF
 );

 OpcodeFlagsExt: array [$00..$FF] of byte =
 (
  OP_MODRM,                        // 00
  OP_MODRM,                        // 01
  OP_MODRM,                        // 02
  OP_MODRM,                        // 03
  OP_NONE,                         // 04
  OP_NONE,                         // 05
  OP_NONE,                         // 06
  OP_NONE,                         // 07
  OP_NONE,                         // 08
  OP_NONE,                         // 09
  OP_NONE,                         // 0A
  OP_NONE,                         // 0B
  OP_NONE,                         // 0C
  OP_MODRM,                        // 0D
  OP_NONE,                         // 0E
  OP_MODRM or OP_DATA_I8,          // 0F
  OP_MODRM,                        // 10
  OP_MODRM,                        // 11
  OP_MODRM,                        // 12
  OP_MODRM,                        // 13
  OP_MODRM,                        // 14
  OP_MODRM,                        // 15
  OP_MODRM,                        // 16
  OP_MODRM,                        // 17
  OP_MODRM,                        // 18
  OP_NONE,                         // 19
  OP_NONE,                         // 1A
  OP_NONE,                         // 1B
  OP_NONE,                         // 1C
  OP_NONE,                         // 1D
  OP_NONE,                         // 1E
  OP_NONE,                         // 1F
  OP_MODRM,                        // 20
  OP_MODRM,                        // 21
  OP_MODRM,                        // 22
  OP_MODRM,                        // 23
  OP_MODRM,                        // 24
  OP_NONE,                         // 25
  OP_MODRM,                        // 26
  OP_NONE,                         // 27
  OP_MODRM,                        // 28
  OP_MODRM,                        // 29
  OP_MODRM,                        // 2A
  OP_MODRM,                        // 2B
  OP_MODRM,                        // 2C
  OP_MODRM,                        // 2D
  OP_MODRM,                        // 2E
  OP_MODRM,                        // 2F
  OP_NONE,                         // 30
  OP_NONE,                         // 31
  OP_NONE,                         // 32
  OP_NONE,                         // 33
  OP_NONE,                         // 34
  OP_NONE,                         // 35
  OP_NONE,                         // 36
  OP_NONE,                         // 37
  OP_NONE,                         // 38
  OP_NONE,                         // 39
  OP_NONE,                         // 3A
  OP_NONE,                         // 3B
  OP_NONE,                         // 3C
  OP_NONE,                         // 3D
  OP_NONE,                         // 3E
  OP_NONE,                         // 3F
  OP_MODRM,                        // 40
  OP_MODRM,                        // 41
  OP_MODRM,                        // 42
  OP_MODRM,                        // 43
  OP_MODRM,                        // 44
  OP_MODRM,                        // 45
  OP_MODRM,                        // 46
  OP_MODRM,                        // 47
  OP_MODRM,                        // 48
  OP_MODRM,                        // 49
  OP_MODRM,                        // 4A
  OP_MODRM,                        // 4B
  OP_MODRM,                        // 4C
  OP_MODRM,                        // 4D
  OP_MODRM,                        // 4E
  OP_MODRM,                        // 4F
  OP_MODRM,                        // 50
  OP_MODRM,                        // 51
  OP_MODRM,                        // 52
  OP_MODRM,                        // 53
  OP_MODRM,                        // 54
  OP_MODRM,                        // 55
  OP_MODRM,                        // 56
  OP_MODRM,                        // 57
  OP_MODRM,                        // 58
  OP_MODRM,                        // 59
  OP_MODRM,                        // 5A
  OP_MODRM,                        // 5B
  OP_MODRM,                        // 5C
  OP_MODRM,                        // 5D
  OP_MODRM,                        // 5E
  OP_MODRM,                        // 5F
  OP_MODRM,                        // 60
  OP_MODRM,                        // 61
  OP_MODRM,                        // 62
  OP_MODRM,                        // 63
  OP_MODRM,                        // 64
  OP_MODRM,                        // 65
  OP_MODRM,                        // 66
  OP_MODRM,                        // 67
  OP_MODRM,                        // 68
  OP_MODRM,                        // 69
  OP_MODRM,                        // 6A
  OP_MODRM,                        // 6B
  OP_MODRM,                        // 6C
  OP_MODRM,                        // 6D
  OP_MODRM,                        // 6E
  OP_MODRM,                        // 6F
  OP_MODRM or OP_DATA_I8,          // 70
  OP_MODRM or OP_DATA_I8,          // 71
  OP_MODRM or OP_DATA_I8,          // 72
  OP_MODRM or OP_DATA_I8,          // 73
  OP_MODRM,                        // 74
  OP_MODRM,                        // 75
  OP_MODRM,                        // 76
  OP_NONE,                         // 77
  OP_NONE,                         // 78
  OP_NONE,                         // 79
  OP_NONE,                         // 7A
  OP_NONE,                         // 7B
  OP_MODRM,                        // 7C
  OP_MODRM,                        // 7D
  OP_MODRM,                        // 7E
  OP_MODRM,                        // 7F
  OP_DATA_PRE66_67 or OP_REL32,    // 80
  OP_DATA_PRE66_67 or OP_REL32,    // 81
  OP_DATA_PRE66_67 or OP_REL32,    // 82
  OP_DATA_PRE66_67 or OP_REL32,    // 83
  OP_DATA_PRE66_67 or OP_REL32,    // 84
  OP_DATA_PRE66_67 or OP_REL32,    // 85
  OP_DATA_PRE66_67 or OP_REL32,    // 86
  OP_DATA_PRE66_67 or OP_REL32,    // 87
  OP_DATA_PRE66_67 or OP_REL32,    // 88
  OP_DATA_PRE66_67 or OP_REL32,    // 89
  OP_DATA_PRE66_67 or OP_REL32,    // 8A
  OP_DATA_PRE66_67 or OP_REL32,    // 8B
  OP_DATA_PRE66_67 or OP_REL32,    // 8C
  OP_DATA_PRE66_67 or OP_REL32,    // 8D
  OP_DATA_PRE66_67 or OP_REL32,    // 8E
  OP_DATA_PRE66_67 or OP_REL32,    // 8F
  OP_MODRM,                        // 90
  OP_MODRM,                        // 91
  OP_MODRM,                        // 92
  OP_MODRM,                        // 93
  OP_MODRM,                        // 94
  OP_MODRM,                        // 95
  OP_MODRM,                        // 96
  OP_MODRM,                        // 97
  OP_MODRM,                        // 98
  OP_MODRM,                        // 99
  OP_MODRM,                        // 9A
  OP_MODRM,                        // 9B
  OP_MODRM,                        // 9C
  OP_MODRM,                        // 9D
  OP_MODRM,                        // 9E
  OP_MODRM,                        // 9F
  OP_NONE,                         // A0
  OP_NONE,                         // A1
  OP_NONE,                         // A2
  OP_MODRM,                        // A3
  OP_MODRM or OP_DATA_I8,          // A4
  OP_MODRM,                        // A5
  OP_NONE,                         // A6
  OP_NONE,                         // A7
  OP_NONE,                         // A8
  OP_NONE,                         // A9
  OP_NONE,                         // AA
  OP_MODRM,                        // AB
  OP_MODRM or OP_DATA_I8,          // AC
  OP_MODRM,                        // AD
  OP_MODRM,                        // AE
  OP_MODRM,                        // AF
  OP_MODRM,                        // B0
  OP_MODRM,                        // B1
  OP_MODRM,                        // B2
  OP_MODRM,                        // B3
  OP_MODRM,                        // B4
  OP_MODRM,                        // B5
  OP_MODRM,                        // B6
  OP_MODRM,                        // B7
  OP_NONE,                         // B8
  OP_NONE,                         // B9
  OP_MODRM or OP_DATA_I8,          // BA
  OP_MODRM,                        // BB
  OP_MODRM,                        // BC
  OP_MODRM,                        // BD
  OP_MODRM,                        // BE
  OP_MODRM,                        // BF
  OP_MODRM,                        // C0
  OP_MODRM,                        // C1
  OP_MODRM or OP_DATA_I8,          // C2
  OP_MODRM,                        // C3
  OP_MODRM or OP_DATA_I8,          // C4
  OP_MODRM or OP_DATA_I8,          // C5
  OP_MODRM or OP_DATA_I8,          // C6
  OP_MODRM,                        // C7
  OP_NONE,                         // C8
  OP_NONE,                         // C9
  OP_NONE,                         // CA
  OP_NONE,                         // CB
  OP_NONE,                         // CC
  OP_NONE,                         // CD
  OP_NONE,                         // CE
  OP_NONE,                         // CF
  OP_MODRM,                        // D0
  OP_MODRM,                        // D1
  OP_MODRM,                        // D2
  OP_MODRM,                        // D3
  OP_MODRM,                        // D4
  OP_MODRM,                        // D5
  OP_MODRM,                        // D6
  OP_MODRM,                        // D7
  OP_MODRM,                        // D8
  OP_MODRM,                        // D9
  OP_MODRM,                        // DA
  OP_MODRM,                        // DB
  OP_MODRM,                        // DC
  OP_MODRM,                        // DD
  OP_MODRM,                        // DE
  OP_MODRM,                        // DF
  OP_MODRM,                        // E0
  OP_MODRM,                        // E1
  OP_MODRM,                        // E2
  OP_MODRM,                        // E3
  OP_MODRM,                        // E4
  OP_MODRM,                        // E5
  OP_MODRM,                        // E6
  OP_MODRM,                        // E7
  OP_MODRM,                        // E8
  OP_MODRM,                        // E9
  OP_MODRM,                        // EA
  OP_MODRM,                        // EB
  OP_MODRM,                        // EC
  OP_MODRM,                        // ED
  OP_MODRM,                        // EE
  OP_MODRM,                        // EF
  OP_MODRM,                        // F0
  OP_MODRM,                        // F1
  OP_MODRM,                        // F2
  OP_MODRM,                        // F3
  OP_MODRM,                        // F4
  OP_MODRM,                        // F5
  OP_MODRM,                        // F6
  OP_MODRM,                        // F7
  OP_MODRM,                        // F8
  OP_MODRM,                        // F9
  OP_MODRM,                        // FA
  OP_MODRM,                        // FB
  OP_MODRM,                        // FC
  OP_MODRM,                        // FD
  OP_MODRM,                        // FE
  OP_NONE                          // FF
 );

{Получение полного размера машинной комманды по указателю на нее }
function SizeOfCode(Code: pointer; pOpcode: ppbyte): dword;
var
 cPtr: pbyte;
 Flags: byte;
 PFX66, PFX67: boolean;
 SibPresent: boolean;
 iMod, iRM, iReg: byte;
 OffsetSize, Add: byte;
 Opcode: byte;
begin
 Result     := 0;
 OffsetSize := 0;
 PFX66      := false;
 PFX67      := false;
 cPtr       := Code;
 {определяем размер преффиксов}
 while cPtr^ in [$2E, $3E, $36, $26, $64, $65, $F0, $F2, $F3, $66, $67] do
  begin
   if cPtr^ = $66 then PFX66 := true;
   if cPtr^ = $67 then PFX67 := true;
   Inc(cPtr);
   if dword(cPtr) > dword(Code) + 16 then Exit;
  end;
 Opcode := cPtr^;
 if pOpcode <> nil then pOpcode^ := cPtr;
 {определяем размер опкода и получаем флаги}
 if cPtr^ = $0F then
  begin
    Inc(cPtr);
    Flags := OpcodeFlagsExt[cPtr^];
  end else
  begin
    Flags := OpcodeFlags[Opcode];
    if Opcode in [$A0..$A3] then PFX66 := PFX67;
  end;
 Inc(cPtr);
 if (Flags and OP_WORD) > 0 then Inc(cPtr);
 {обрабатываем MOD r/m}
 if (Flags and OP_MODRM) > 0 then
  begin
   iMod :=  cPtr^ shr 6;
   iReg := (cPtr^ and $38) shr 3;
   iRM  :=  cPtr^ and 7;
   Inc(cPtr);
   {опкоды F6 и F7 - Immediate присутствует только при iReg = 0}
   if (Opcode = $F6) and (iReg = 0) then Flags := Flags or OP_DATA_I8;
   if (Opcode = $F7) and (iReg = 0) then Flags := Flags or OP_DATA_PRE66_67;  
   {обрабатываем SIB и Offset}
   SibPresent := (not PFX67) and (iRM = 4);
   case iMod of
     0: begin
          if PFX67 and (iRM = 6) then OffsetSize := 2;
          if (not PFX67) and (iRM = 5) then OffsetSize := 4;    
        end;
     1: OffsetSize := 1;
     2: if PFX67 then OffsetSize := 2 else OffsetSize := 4;
     3: SibPresent := false;
   end;
   if SibPresent then
    begin
     if (cPtr^ and 7 = 5) and (iMod in [0, 2]) then OffsetSize := 4;
     Inc(cPtr);
    end;
   Inc(cPtr, OffsetSize);
  end;
 {обрабатываем IMM значения}
 if (Flags and OP_DATA_I8)  > 0 then Inc(cPtr);
 if (Flags and OP_DATA_I16) > 0 then Inc(cPtr, 2);
 if (Flags and OP_DATA_I32) > 0 then Inc(cPtr, 4);
 if PFX66 then Add := 2 else Add := 4;
 if (Flags and OP_DATA_PRE66_67) > 0 then Inc(cPtr, Add);
 Result := dword(cPtr) - dword(Code);
end;


{ Получение размера функции по указател на нее (размер до первой комманды RET) }
function SizeOfProc(Proc: pointer): dword;
var
  Length: dword;
  pOpcode: pbyte;
begin
  Result := 0;
  repeat
    Length := SizeOfCode(Proc, @pOpcode);
    Inc(Result, Length);
    if (Length = 1) and (pOpcode^ = $C3) then Break;
    Proc := pointer(dword(Proc) + Length);
  until Length = 0;
end;

{определение того, имеет ли комманда rel32 offset}
function IsRelativeCmd(pOpcode: pbyte): boolean;
var
 Flags: byte;
begin
 if pOpcode^ = $0F then Flags := OpcodeFlagsExt[pbyte(dword(pOpcode) + 1)^]
    else Flags := OpcodeFlags[pOpcode^];
 Result := Flags and OP_REL32 > 0;
end;


end.
