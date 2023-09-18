alter table TSelfRT 
   drop foreign key FK_TSELFRT_TSELFRT_TAGT;

alter table TSelfRT 
   drop foreign key FK_TSELFRT_TSELFRT2_TAGT;


alter table TSelfRT 
   drop foreign key FK_TSELFRT_TSELFRT_TAGT;

alter table TSelfRT 
   drop foreign key FK_TSELFRT_TSELFRT2_TAGT;

drop table if exists TSelfRT;

create table TSelfRT
(
   Tag_T_Id             char(21) not null  comment '',
   T_Id                 char(21) not null  comment '',
   primary key (Tag_T_Id, T_Id),
   State                int                  null,
   CreateTime           datetime             null,
   LastUpdateTime       datetime             null,
   ExclusiveKey         int                  null,
   IsLocked             tinyint                 null
);

alter table TSelfRT add constraint FK_TSELFRT_TSELFRT_TAGT foreign key (Tag_T_Id)
      references TagT (T_Id) on delete restrict on update restrict;

alter table TSelfRT add constraint FK_TSELFRT_TSELFRT2_TAGT foreign key (T_Id)
      references TagT (T_Id) on delete restrict on update restrict;

