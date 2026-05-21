 <div class="row">
     <div class="col-md-4">
         <div class="form-group">
             <label class="form-label" for="exampleFormControlSelect1">{{ translate('messages.type') }}<span
                     class="input-label-secondary"></span></label>
             <select name="type" id="type" data-placeholder="{{ translate('messages.select_type') }}"
                 class="form-control" required title="Select Type">
                 <option value="all">{{ translate('messages.all_data') }}</option>
                 <option value="date_wise">{{ translate('messages.date_wise') }}</option>
                 <option value="id_wise">{{ translate('messages.id_wise') }}</option>
             </select>
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group id_wise">
             <label class="form-label" for="exampleFormControlSelect1">{{ translate('messages.start_id') }}<span
                     class="input-label-secondary"></span></label>
             <input type="number" name="start_id" class="form-control"
                 placeholder="{{ translate('messages.example') }}: 1">
         </div>
         <div class="form-group date_wise">
             <label class="form-label" for="exampleFormControlSelect1">{{ translate('messages.from_date') }}<span
                     class="input-label-secondary"></span></label>
             <input type="date" name="from_date" id="date_from" data-error-message="{{ translate('messages.from_date_cannot_be_greater_than_to_date') }}" class="form-control">
         </div>
     </div>
     <div class="col-md-4">
         <div class="form-group id_wise">
             <label class="form-label" for="exampleFormControlSelect1">{{ translate('messages.end_id') }}<span
                     class="input-label-secondary"></span></label>
             <input type="number" name="end_id" class="form-control"
                 placeholder="{{ translate('messages.example') }}: 10">
         </div>
         <div class="form-group date_wise">
             <label class="input-label text-capitalize"
                 for="exampleFormControlSelect1">{{ translate('messages.to_date') }}<span
                     class="input-label-secondary"></span></label>
             <input type="date" name="to_date" id="date_to" class="form-control"
                 placeholder="{{ translate('messages.example') }}: 2025-11-11">
         </div>
     </div>
 </div>
 <div class="btn--container justify-content-end">
     <button class="btn btn--reset" type="reset">{{ translate('messages.reset') }}</button>
     <button class="btn btn--primary" type="submit">{{ translate('messages.export') }}</button>
 </div>
